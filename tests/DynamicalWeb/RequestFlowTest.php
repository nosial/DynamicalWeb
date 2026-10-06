<?php

    namespace DynamicalWeb;

    use DynamicalWeb\Tests\Fixtures\MemcachedServer;
    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;
    use PHPUnit\Framework\TestCase;

    /**
     * Runs requests end to end through DynamicalWeb::handleRequest(), each in its own process
     * (see tests/Fixtures/run_request.php).
     */
    class RequestFlowTest extends TestCase
    {
        /** Module code that hands the response to the runner and logs that the module ran */
        private const string CAPTURE = '<?php $GLOBALS["dw_test_response"] = \DynamicalWeb\WebSession::getResponse(); $GLOBALS["dw_test_log"][] = "module"; ?>';

        private static array $originalEnv = [];

        public static function setUpBeforeClass(): void
        {
            foreach (['MEMCACHED_ENABLED', 'MEMCACHED_HOST', 'MEMCACHED_PORT'] as $key)
            {
                self::$originalEnv[$key] = getenv($key);
            }
        }

        public static function tearDownAfterClass(): void
        {
            WebSessionFixture::reset();
            foreach (self::$originalEnv as $key => $value)
            {
                putenv($value === false ? $key : $key . '=' . $value);
            }
        }

        private function runRequest(array $spec): array
        {
            $specFile = tempnam(sys_get_temp_dir(), 'dw_spec_');
            file_put_contents($specFile, json_encode($spec));

            $environment = getenv();
            if (getenv('NCC_BUILD_OUTPUT_PATH') === false)
            {
                $environment['NCC_BUILD_OUTPUT_PATH'] = realpath(__DIR__ . '/../../target/release/net.nosial.dynamicalweb.ncc');
            }

            $process = proc_open(
                [PHP_BINARY, '-d', 'display_errors=stderr', __DIR__ . '/../Fixtures/run_request.php', $specFile],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                null,
                $environment
            );
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
            unlink($specFile);

            $result = json_decode($stdout, true);
            $this->assertIsArray($result, "Request runner failed:\n" . $stdout . "\n" . $stderr);
            return $result;
        }

        private function routes(array $extraRoutes = []): array
        {
            return array_merge([
                ['id' => 'dashboard', 'path' => '/', 'module' => 'index.phtml'],
                ['id' => 'logout', 'path' => '/logout', 'module' => 'logout.phtml'],
                ['id' => 'webhook', 'path' => '/webhook', 'module' => 'index.phtml', 'csrf_exempt' => true],
            ], $extraRoutes);
        }

        // Configured headers

        public function testConfiguredHeadersAreAppliedAndModulesCanOverrideThem(): void
        {
            $result = $this->runRequest([
                'config' => [
                    'application' => ['headers' => ['X-Frame-Options' => 'DENY', 'Referrer-Policy' => 'same-origin']],
                    'router' => ['routes' => $this->routes()],
                ],
                'modules' => ['index.phtml' => self::CAPTURE . '<?php \DynamicalWeb\WebSession::getResponse()->setHeader("Referrer-Policy", "no-referrer"); ?>page'],
            ]);

            $this->assertSame(200, $result['status']);
            $this->assertSame('page', $result['body']);
            $this->assertSame('DENY', $result['headers']['X-Frame-Options']);
            $this->assertSame('no-referrer', $result['headers']['Referrer-Policy']);
            $this->assertSame('DynamicalWeb', $result['headers']['X-Powered-By']);
        }

        public function testNoConfiguredHeadersByDefault(): void
        {
            $result = $this->runRequest([
                'config' => ['router' => ['routes' => $this->routes()]],
                'modules' => ['index.phtml' => self::CAPTURE],
            ]);

            $this->assertArrayNotHasKey('X-Frame-Options', $result['headers']);
        }

        // Pre/post request hooks

        public function testRequestHooksRespectRouteFilters(): void
        {
            $log = fn(string $name) => '<?php $GLOBALS["dw_test_log"][] = "' . $name . '";';
            $config = [
                'application' => [
                    'pre_request' => [
                        'pre/all.php',
                        ['module' => 'pre/not_logout.php', 'except' => ['logout']],
                        ['module' => 'pre/logout_only.php', 'only' => ['logout']],
                    ],
                    'post_request' => [['module' => 'post/not_logout.php', 'except' => 'logout']],
                ],
                'router' => ['routes' => $this->routes()],
            ];
            $modules = [
                'index.phtml' => self::CAPTURE,
                'logout.phtml' => self::CAPTURE,
                'pre/all.php' => $log('all'),
                'pre/not_logout.php' => $log('not_logout'),
                'pre/logout_only.php' => $log('logout_only'),
                'post/not_logout.php' => $log('post_not_logout'),
            ];

            $dashboard = $this->runRequest(['config' => $config, 'modules' => $modules]);
            $this->assertSame(['all', 'not_logout', 'module', 'post_not_logout'], $dashboard['log']);

            $logout = $this->runRequest(['config' => $config, 'modules' => $modules, 'server' => ['REQUEST_URI' => '/logout']]);
            $this->assertSame(['all', 'logout_only', 'module'], $logout['log']);
        }

        // CSRF protection

        public function testPostWithoutTokenIsAllowedWhenCsrfProtectionIsOff(): void
        {
            $result = $this->runRequest([
                'config' => ['router' => ['routes' => $this->routes()]],
                'modules' => ['index.phtml' => self::CAPTURE . 'ok'],
                'server' => ['REQUEST_METHOD' => 'POST'],
            ]);

            $this->assertSame(200, $result['status']);
            $this->assertSame(['module'], $result['log']);
        }

        private function csrfConfig(array $router = []): array
        {
            return [
                'application' => [
                    'csrf_protection' => true,
                    'pre_request' => ['pre/log.php'],
                    'headers' => ['X-Frame-Options' => 'DENY'],
                ],
                'router' => $router + ['routes' => $this->routes()],
            ];
        }

        private function csrfModules(): array
        {
            return [
                'index.phtml' => self::CAPTURE . 'ok',
                'pre/log.php' => '<?php $GLOBALS["dw_test_log"][] = "pre";',
                'errors/403.phtml' => 'custom forbidden page',
            ];
        }

        public function testPostWithoutTokenIsRejected(): void
        {
            $result = $this->runRequest([
                'config' => $this->csrfConfig(),
                'modules' => $this->csrfModules(),
                'server' => ['REQUEST_METHOD' => 'POST'],
            ]);

            $this->assertSame(403, $result['status']);
            $this->assertSame('403 Forbidden: missing or invalid CSRF token', $result['body']);
            $this->assertSame([], $result['log'], 'Neither pre-request scripts nor the module may run');
        }

        public function testRejectedScriptRequestsGetJson(): void
        {
            foreach ([['HTTP_X_CSRF_TOKEN' => 'wrong'], ['HTTP_ACCEPT' => 'application/json']] as $server)
            {
                $result = $this->runRequest([
                    'config' => $this->csrfConfig(),
                    'modules' => $this->csrfModules(),
                    'server' => ['REQUEST_METHOD' => 'DELETE'] + $server,
                ]);

                $this->assertSame(403, $result['status']);
                $this->assertSame(['error' => 'csrf_failed'], json_decode($result['body'], true));
            }
        }

        public function testRejectionRendersConfiguredForbiddenHandler(): void
        {
            $result = $this->runRequest([
                'config' => $this->csrfConfig(['response_handlers' => [403 => 'errors/403.phtml']]),
                'modules' => $this->csrfModules(),
                'server' => ['REQUEST_METHOD' => 'PUT'],
            ]);

            $this->assertSame(403, $result['status']);
            $this->assertSame('custom forbidden page', $result['body']);
        }

        public function testSafeMethodsAndExemptRoutesAreNotChecked(): void
        {
            $get = $this->runRequest(['config' => $this->csrfConfig(), 'modules' => $this->csrfModules()]);
            $this->assertSame(200, $get['status']);
            $this->assertSame(['pre', 'module'], $get['log']);

            $exempt = $this->runRequest([
                'config' => $this->csrfConfig(),
                'modules' => $this->csrfModules(),
                'server' => ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/webhook'],
            ]);
            $this->assertSame(200, $exempt['status']);
            $this->assertSame(['pre', 'module'], $exempt['log']);
        }

        public function testPostWithValidTokenIsAccepted(): void
        {
            $port = MemcachedServer::start();
            if ($port === null)
            {
                $this->markTestSkipped('The memcached extension and server binary are required');
            }

            $env = ['MEMCACHED_ENABLED' => '1', 'MEMCACHED_HOST' => '127.0.0.1', 'MEMCACHED_PORT' => (string)$port];
            foreach ($env as $name => $value)
            {
                putenv($name . '=' . $value);
            }

            // Issue a session and token as an earlier page render would
            $response = WebSessionFixture::install(WebSessionFixture::makeRequest());
            $token = WebSession::getCsrfToken();
            $sessionId = $response->getCookie('web_session')->getValue();
            WebSessionFixture::reset();

            $spec = [
                'config' => $this->csrfConfig(),
                'modules' => $this->csrfModules(),
                'env' => $env,
                'cookies' => ['web_session' => $sessionId],
            ];

            $field = $this->runRequest($spec + ['server' => ['REQUEST_METHOD' => 'POST'], 'post' => ['csrf_token' => $token]]);
            $this->assertSame(200, $field['status']);
            $this->assertSame(['pre', 'module'], $field['log']);

            $header = $this->runRequest($spec + ['server' => ['REQUEST_METHOD' => 'POST', 'HTTP_X_CSRF_TOKEN' => $token]]);
            $this->assertSame(200, $header['status']);

            $wrong = $this->runRequest($spec + ['server' => ['REQUEST_METHOD' => 'POST'], 'post' => ['csrf_token' => strrev($token)]]);
            $this->assertSame(403, $wrong['status']);
        }

        // Respond-and-stop helpers

        private function helperRun(string $code, array $router = []): array
        {
            return $this->runRequest([
                'config' => ['router' => $router + ['routes' => $this->routes([
                    ['id' => 'report', 'path' => '/reports/{uuid}', 'module' => 'index.phtml'],
                ])]],
                'modules' => [
                    'index.phtml' => self::CAPTURE . '<?php ' . $code . ' $GLOBALS["dw_test_log"][] = "after"; ?>',
                    'errors/403.phtml' => 'forbidden page',
                    'errors/404.phtml' => 'missing page',
                ],
            ]);
        }

        public function testRedirectToRouteStopsTheRequest(): void
        {
            $result = $this->helperRun('\DynamicalWeb\WebSession::redirectToRoute("report", ["uuid" => "a b"], ["tab" => "evidence"]);');

            $this->assertSame(302, $result['status']);
            $this->assertSame('/reports/a%20b?tab=evidence', $result['headers']['Location']);
            $this->assertSame(['module'], $result['log']);
        }

        public function testRedirectToWithStatusCode(): void
        {
            $result = $this->helperRun('\DynamicalWeb\WebSession::redirectTo("https://example.com/", \DynamicalWeb\Enums\ResponseCode::MOVED_PERMANENTLY);');

            $this->assertSame(301, $result['status']);
            $this->assertSame('https://example.com/', $result['headers']['Location']);
        }

        public function testRespondJson(): void
        {
            $result = $this->helperRun('\DynamicalWeb\WebSession::respondJson(["created" => true], 201);');

            $this->assertSame(201, $result['status']);
            $this->assertSame(['created' => true], json_decode($result['body'], true));
            $this->assertSame(['module'], $result['log']);
        }

        public function testAbortRendersConfiguredHandler(): void
        {
            $handlers = ['response_handlers' => [403 => 'errors/403.phtml', 404 => 'errors/404.phtml']];

            $forbidden = $this->helperRun('\DynamicalWeb\WebSession::abort(\DynamicalWeb\Enums\ResponseCode::FORBIDDEN);', $handlers);
            $this->assertSame(403, $forbidden['status']);
            $this->assertSame('forbidden page', $forbidden['body']);
            $this->assertSame(['module'], $forbidden['log']);

            $missing = $this->helperRun('\DynamicalWeb\WebSession::abort(404);', $handlers);
            $this->assertSame(404, $missing['status']);
            $this->assertSame('missing page', $missing['body']);
        }

        public function testAbortWithoutHandlerOrWithMessageSendsText(): void
        {
            $plain = $this->helperRun('\DynamicalWeb\WebSession::abort(403);');
            $this->assertSame(403, $plain['status']);
            $this->assertSame('403 Forbidden', $plain['body']);

            $message = $this->helperRun('\DynamicalWeb\WebSession::abort(409, "Already closed");', ['response_handlers' => [409 => 'errors/403.phtml']]);
            $this->assertSame(409, $message['status']);
            $this->assertSame('Already closed', $message['body']);
            $this->assertSame('text/plain', $message['content_type']);
        }
    }
