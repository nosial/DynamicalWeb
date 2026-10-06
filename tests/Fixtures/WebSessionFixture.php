<?php

    namespace DynamicalWeb\Tests\Fixtures;

    use DynamicalWeb\DynamicalWeb;
    use DynamicalWeb\Enums\RequestMethod;
    use DynamicalWeb\Objects\Locale;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;
    use DynamicalWeb\Objects\WebConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\WebSession;
    use ReflectionClass;

    /**
     * Builds the request state WebSession normally gets from a real HTTP request, so classes that read
     * WebSession can be tested without a web server.
     */
    class WebSessionFixture
    {
        /**
         * Builds a Request without reading $_SERVER.
         *
         * @param array $options method, path, host, secure, headers, query, body, form, cookies, client_ip, user_agent
         * @return Request
         */
        public static function makeRequest(array $options = []): Request
        {
            $reflection = new ReflectionClass(Request::class);
            $request = $reflection->newInstanceWithoutConstructor();

            $headers = $options['headers'] ?? [];
            $values = [
                'id' => 'test-request',
                'method' => $options['method'] ?? RequestMethod::GET,
                'url' => 'http://localhost' . ($options['path'] ?? '/'),
                'path' => $options['path'] ?? '/',
                'host' => $options['host'] ?? 'localhost',
                'httpVersion' => '1.1',
                'isSecure' => $options['secure'] ?? false,
                'headers' => $headers,
                'headersLowerMap' => array_change_key_case($headers),
                'queryParameters' => $options['query'] ?? [],
                'bodyParameters' => $options['body'] ?? [],
                'formParameters' => $options['form'] ?? [],
                'pathParameters' => [],
                'files' => [],
                'uploadedFiles' => [],
                'cookies' => $options['cookies'] ?? [],
                'detectedLanguage' => null,
                'clientIp' => $options['client_ip'] ?? '203.0.113.10',
                'rawBody' => null,
                'userAgent' => null,
                'rawUserAgentString' => $options['user_agent'] ?? 'TestAgent/1.0',
            ];

            foreach ($values as $property => $value)
            {
                $reflection->getProperty($property)->setValue($request, $value);
            }

            return $request;
        }

        /**
         * Builds a DynamicalWeb instance from a configuration array without an imported ncc package.
         *
         * @param array $configuration Web configuration data; 'application' and 'router' get defaults
         * @param string $webRootPath Directory that module paths resolve against
         * @return DynamicalWeb
         */
        public static function makeInstance(array $configuration = [], string $webRootPath = '/nonexistent'): DynamicalWeb
        {
            $configuration['application'] = ($configuration['application'] ?? []) + [
                'name' => 'TestApp',
                'root' => 'public',
                'resources' => 'resources',
                'report_errors' => false,
            ];
            $configuration['router'] = ($configuration['router'] ?? []) + [
                'base_path' => '/',
                'routes' => [],
            ];

            $reflection = new ReflectionClass(DynamicalWeb::class);
            $instance = $reflection->newInstanceWithoutConstructor();
            $values = [
                'package' => 'net.nosial.test',
                'configurationPath' => null,
                'webRootPath' => $webRootPath,
                'webResourcesPath' => $webRootPath,
                'availableLocalesPaths' => [],
                'availableLocales' => [],
                'webConfiguration' => new WebConfiguration($configuration),
            ];

            foreach ($values as $property => $value)
            {
                $reflection->getProperty($property)->setValue($instance, $value);
            }

            return $instance;
        }

        /**
         * Installs request state into WebSession, replacing whatever was there.
         */
        public static function install(Request $request, ?Response $response = null, ?Route $route = null, ?Locale $locale = null, ?DynamicalWeb $instance = null): Response
        {
            self::reset();
            $response ??= new Response();

            $reflection = new ReflectionClass(WebSession::class);
            $reflection->getProperty('instance')->setValue(null, $instance);
            $reflection->getProperty('request')->setValue(null, $request);
            $reflection->getProperty('response')->setValue(null, $response);
            $reflection->getProperty('currentRoute')->setValue(null, $route);
            $reflection->getProperty('locale')->setValue(null, $locale);
            $reflection->getProperty('variables')->setValue(null, []);
            FakeMemcached::attachToWebSession();

            return $response;
        }

        /**
         * Clears WebSession state without sending a response (endSession() would emit headers).
         */
        public static function reset(): void
        {
            $reflection = new ReflectionClass(WebSession::class);
            foreach (['instance', 'request', 'response', 'module', 'currentRoute', 'locale', 'exception', 'websocket', 'cookieSessionManager'] as $property)
            {
                $reflection->getProperty($property)->setValue(null, null);
            }
            $reflection->getProperty('variables')->setValue(null, []);
            $reflection->getProperty('loadedCookieSessions')->setValue(null, []);
            $reflection->getProperty('createdCookieSessions')->setValue(null, []);

            $functions = new ReflectionClass(\DynamicalWeb\Html\Functions::class);
            $functions->getProperty('activeLocaleSection')->setValue(null, null);
        }

        /**
         * Simulates the next request: the browser sends back the cookies the previous response set.
         *
         * @param Response $previous The previous response
         * @param array $options Request options, as for makeRequest()
         * @return Response The new response
         */
        public static function nextRequest(Response $previous, array $options = [], ?Route $route = null, ?Locale $locale = null, ?DynamicalWeb $instance = null): Response
        {
            $cookies = $options['cookies'] ?? [];
            foreach ($previous->getCookies() as $cookie)
            {
                $cookies[$cookie->getName()] = $cookie->getValue();
            }
            $options['cookies'] = $cookies;

            return self::install(self::makeRequest($options), null, $route, $locale, $instance);
        }
    }
