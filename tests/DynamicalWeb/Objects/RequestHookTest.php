<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Objects\WebConfiguration\ApplicationConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\RequestHook;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class RequestHookTest extends TestCase
    {
        private function route(?string $id): Route
        {
            return new Route(['id' => $id, 'path' => '/' . ($id ?? 'anonymous'), 'module' => 'x.phtml']);
        }

        private function application(array $extra = []): ApplicationConfiguration
        {
            return new ApplicationConfiguration($extra + [
                'name' => 'TestApp',
                'root' => 'public',
                'resources' => 'resources',
                'report_errors' => false,
            ]);
        }

        // RequestHook

        public function testStringEntryRunsEverywhere(): void
        {
            $hook = new RequestHook('pre/server.phtml');

            $this->assertSame('pre/server.phtml', $hook->getModule());
            $this->assertTrue($hook->appliesTo($this->route('dashboard')));
            $this->assertTrue($hook->appliesTo($this->route(null)));
            $this->assertTrue($hook->appliesTo(null));
            $this->assertTrue($hook->appliesTo($this->route('dashboard'), true));
            $this->assertSame(['module' => 'pre/server.phtml'], $hook->toArray());
        }

        public function testExceptSkipsListedRoutes(): void
        {
            $hook = new RequestHook(['module' => 'pre/server.phtml', 'except' => ['configuration_error', 'logout']]);

            $this->assertFalse($hook->appliesTo($this->route('logout')));
            $this->assertFalse($hook->appliesTo($this->route('configuration_error')));
            $this->assertTrue($hook->appliesTo($this->route('dashboard')));
            $this->assertTrue($hook->appliesTo($this->route(null)));
        }

        public function testOnlyLimitsToListedRoutes(): void
        {
            $hook = new RequestHook(['module' => 'pre/admin.phtml', 'only' => 'operators']);

            $this->assertSame(['operators'], $hook->getOnly());
            $this->assertTrue($hook->appliesTo($this->route('operators')));
            $this->assertFalse($hook->appliesTo($this->route('dashboard')));
            $this->assertFalse($hook->appliesTo($this->route(null)));
        }

        public function testWebSocketFlag(): void
        {
            $hook = new RequestHook(['module' => 'pre/auth.phtml', 'websocket' => false]);

            $this->assertFalse($hook->runsForWebSocket());
            $this->assertFalse($hook->appliesTo($this->route('dashboard'), true));
            $this->assertTrue($hook->appliesTo($this->route('dashboard'), false));
        }

        public function testRoundTrip(): void
        {
            $data = ['module' => 'pre/auth.phtml', 'only' => ['a', 'b'], 'except' => ['b'], 'websocket' => false];
            $this->assertSame($data, RequestHook::fromArray($data)->toArray());
        }

        public function testEntryWithoutModuleIsRejected(): void
        {
            $this->expectException(InvalidArgumentException::class);
            new RequestHook(['except' => ['logout']]);
        }

        // ApplicationConfiguration

        public function testGetPreRequestStillReturnsModulePaths(): void
        {
            $config = $this->application(['pre_request' => [
                'pre/server.phtml',
                ['module' => 'pre/auth.phtml', 'except' => ['logout']],
            ]]);

            $this->assertSame(['pre/server.phtml', 'pre/auth.phtml'], $config->getPreRequest());
            $this->assertCount(2, $config->getPreRequestHooks());
            $this->assertSame(['logout'], $config->getPreRequestHooks()[1]->getExcept());
        }

        public function testUnsetHooksKeepReturningNull(): void
        {
            $config = $this->application();

            $this->assertNull($config->getPreRequest());
            $this->assertNull($config->getPostRequest());
            $this->assertSame([], $config->getPreRequestHooks());
            $this->assertSame([], $config->getPostRequestHooks());
        }

        public function testPostRequestHooks(): void
        {
            $config = $this->application(['post_request' => [['module' => 'post/log.phtml', 'only' => ['api']]]]);

            $this->assertSame(['post/log.phtml'], $config->getPostRequest());
            $this->assertFalse($config->getPostRequestHooks()[0]->appliesTo($this->route('dashboard')));
        }

        public function testHookConfigurationRoundTrips(): void
        {
            $entries = ['pre/server.phtml', ['module' => 'pre/auth.phtml', 'websocket' => false]];
            $restored = ApplicationConfiguration::fromArray($this->application(['pre_request' => $entries])->toArray());

            $this->assertSame(['pre/server.phtml', 'pre/auth.phtml'], $restored->getPreRequest());
            $this->assertFalse($restored->getPreRequestHooks()[1]->runsForWebSocket());
        }

        public function testCsrfAndHeadersDefaults(): void
        {
            $config = $this->application();

            $this->assertFalse($config->isCsrfProtectionEnabled());
            $this->assertSame([], $config->getHeaders());
        }

        public function testCsrfAndHeadersConfiguration(): void
        {
            $config = $this->application([
                'csrf_protection' => true,
                'headers' => ['X-Frame-Options' => 'DENY', 'X-Content-Type-Options' => 'nosniff'],
            ]);

            $this->assertTrue($config->isCsrfProtectionEnabled());
            $this->assertSame(['X-Frame-Options' => 'DENY', 'X-Content-Type-Options' => 'nosniff'], $config->getHeaders());

            $restored = ApplicationConfiguration::fromArray($config->toArray());
            $this->assertTrue($restored->isCsrfProtectionEnabled());
            $this->assertSame($config->getHeaders(), $restored->getHeaders());
        }

        // Route

        public function testRouteCsrfExempt(): void
        {
            $plain = new Route(['id' => 'a', 'path' => '/a', 'module' => 'a.phtml']);
            $exempt = new Route(['id' => 'b', 'path' => '/b', 'module' => 'b.phtml', 'csrf_exempt' => true]);

            $this->assertFalse($plain->isCsrfExempt());
            $this->assertArrayNotHasKey('csrf_exempt', $plain->toArray());
            $this->assertTrue($exempt->isCsrfExempt());
            $this->assertTrue(Route::fromArray($exempt->toArray())->isCsrfExempt());
        }
    }
