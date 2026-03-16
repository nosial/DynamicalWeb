<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Enums\XssProtectionLevel;
    use DynamicalWeb\Objects\WebConfiguration\ApplicationConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\Objects\WebConfiguration\RouterConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Section;
    use PHPUnit\Framework\TestCase;

    class WebConfigurationTest extends TestCase
    {
        private function createMinimalConfigData(): array
        {
            return [
                'application' => [
                    'name' => 'TestApp',
                    'root' => 'public',
                    'resources' => 'resources',
                    'report_errors' => true,
                ],
                'router' => [
                    'base_path' => '',
                    'routes' => [
                        [
                            'id' => 'home',
                            'path' => '/',
                            'module' => 'index.phtml',
                            'locale_id' => 'home',
                        ],
                        [
                            'path' => '/about',
                            'module' => 'about.phtml',
                        ],
                    ],
                ],
            ];
        }

        private function createFullConfigData(): array
        {
            return [
                'application' => [
                    'name' => 'FullApp',
                    'root' => 'web',
                    'resources' => 'assets',
                    'default_locale' => 'en',
                    'report_errors' => false,
                    'xss_level' => 2,
                    'pre_request' => ['middleware/auth.php'],
                    'post_request' => ['middleware/log.php'],
                    'debug_panel' => true,
                    'disable_apcu' => true,
                    'disable_default_headers' => true,
                    'static_cache_max_age' => 7200,
                    'apcu_content_max_size' => 524288,
                    'apcu_content_ttl' => 1800,
                    'apcu_meta_ttl' => 30,
                    'apcu_config_ttl' => 120,
                ],
                'router' => [
                    'base_path' => '/app',
                    'response_handlers' => [
                        404 => 'errors/404.phtml',
                        500 => 'errors/500.phtml',
                    ],
                    'routes' => [
                        [
                            'id' => 'user_profile',
                            'path' => '/users/{id}',
                            'module' => 'user/profile.phtml',
                            'locale_id' => 'users',
                            'allowed_methods' => ['GET'],
                        ],
                        [
                            'path' => '/api/data',
                            'module' => 'api/data.php',
                            'allowed_methods' => ['GET', 'POST'],
                        ],
                    ],
                ],
                'locales' => [
                    'en' => 'locales/en.yml',
                    'fr' => 'locales/fr.yml',
                ],
                'sections' => [
                    'header' => [
                        'module' => 'sections/header.phtml',
                        'locale_id' => 'header',
                    ],
                    'footer' => [
                        'module' => 'sections/footer.phtml',
                    ],
                ],
            ];
        }

        // ApplicationConfiguration tests

        public function testApplicationConfigurationMinimal(): void
        {
            $config = new ApplicationConfiguration([
                'name' => 'TestApp',
                'root' => 'public',
                'resources' => 'resources',
                'report_errors' => true,
            ]);

            $this->assertEquals('TestApp', $config->getName());
            $this->assertEquals('public', $config->getRoot());
            $this->assertEquals('resources', $config->getResources());
            $this->assertTrue($config->errorReportingEnabled());
            $this->assertNull($config->getDefaultLocale());
            $this->assertEquals(XssProtectionLevel::DISABLED, $config->getXssLevel());
            $this->assertNull($config->getPreRequest());
            $this->assertNull($config->getPostRequest());
            $this->assertFalse($config->isDebugPanelEnabled());
            $this->assertFalse($config->isApcuDisabled());
            $this->assertFalse($config->isDefaultHeadersDisabled());
            $this->assertEquals(3600, $config->getStaticCacheMaxAge());
            $this->assertEquals(262144, $config->getApcuContentMaxSize());
            $this->assertEquals(3600, $config->getApcuContentTtl());
            $this->assertEquals(10, $config->getApcuMetaTtl());
            $this->assertEquals(60, $config->getApcuConfigTtl());
        }

        public function testApplicationConfigurationFull(): void
        {
            $data = $this->createFullConfigData();
            $config = new ApplicationConfiguration($data['application']);

            $this->assertEquals('FullApp', $config->getName());
            $this->assertEquals('en', $config->getDefaultLocale());
            $this->assertFalse($config->errorReportingEnabled());
            $this->assertEquals(XssProtectionLevel::MEDIUM, $config->getXssLevel());
            $this->assertEquals(['middleware/auth.php'], $config->getPreRequest());
            $this->assertEquals(['middleware/log.php'], $config->getPostRequest());
            $this->assertTrue($config->isDebugPanelEnabled());
            $this->assertTrue($config->isApcuDisabled());
            $this->assertTrue($config->isDefaultHeadersDisabled());
            $this->assertEquals(7200, $config->getStaticCacheMaxAge());
            $this->assertEquals(524288, $config->getApcuContentMaxSize());
            $this->assertEquals(1800, $config->getApcuContentTtl());
            $this->assertEquals(30, $config->getApcuMetaTtl());
            $this->assertEquals(120, $config->getApcuConfigTtl());
        }

        public function testApplicationConfigurationToArrayRoundTrip(): void
        {
            $data = $this->createFullConfigData()['application'];
            $config = new ApplicationConfiguration($data);
            $restored = ApplicationConfiguration::fromArray($config->toArray());

            $this->assertEquals($config->getName(), $restored->getName());
            $this->assertEquals($config->getRoot(), $restored->getRoot());
            $this->assertEquals($config->getResources(), $restored->getResources());
            $this->assertEquals($config->getDefaultLocale(), $restored->getDefaultLocale());
            $this->assertEquals($config->errorReportingEnabled(), $restored->errorReportingEnabled());
            $this->assertEquals($config->getXssLevel(), $restored->getXssLevel());
            $this->assertEquals($config->getPreRequest(), $restored->getPreRequest());
            $this->assertEquals($config->getPostRequest(), $restored->getPostRequest());
            $this->assertEquals($config->isDebugPanelEnabled(), $restored->isDebugPanelEnabled());
            $this->assertEquals($config->isApcuDisabled(), $restored->isApcuDisabled());
            $this->assertEquals($config->isDefaultHeadersDisabled(), $restored->isDefaultHeadersDisabled());
            $this->assertEquals($config->getStaticCacheMaxAge(), $restored->getStaticCacheMaxAge());
            $this->assertEquals($config->getApcuContentMaxSize(), $restored->getApcuContentMaxSize());
            $this->assertEquals($config->getApcuContentTtl(), $restored->getApcuContentTtl());
            $this->assertEquals($config->getApcuMetaTtl(), $restored->getApcuMetaTtl());
            $this->assertEquals($config->getApcuConfigTtl(), $restored->getApcuConfigTtl());
        }

        // Route tests

        public function testRouteMinimal(): void
        {
            $route = new Route([
                'path' => '/about',
                'module' => 'about.phtml',
            ]);

            $this->assertNull($route->getId());
            $this->assertEquals('/about', $route->getPath());
            $this->assertEquals('about.phtml', $route->getModule());
            $this->assertNull($route->getLocaleId());
            $this->assertEquals(['*'], $route->getAllowedMethods());
        }

        public function testRouteFull(): void
        {
            $route = new Route([
                'id' => 'user_profile',
                'path' => '/users/{id}',
                'module' => 'user/profile.phtml',
                'locale_id' => 'users',
                'allowed_methods' => ['GET', 'POST'],
            ]);

            $this->assertEquals('user_profile', $route->getId());
            $this->assertEquals('/users/{id}', $route->getPath());
            $this->assertEquals('user/profile.phtml', $route->getModule());
            $this->assertEquals('users', $route->getLocaleId());
            $this->assertEquals(['GET', 'POST'], $route->getAllowedMethods());
        }

        public function testRouteToArrayRoundTrip(): void
        {
            $original = new Route([
                'id' => 'test',
                'path' => '/test/{slug}',
                'module' => 'test.phtml',
                'locale_id' => 'test_page',
                'allowed_methods' => ['GET'],
            ]);

            $restored = Route::fromArray($original->toArray());
            $this->assertEquals($original->getId(), $restored->getId());
            $this->assertEquals($original->getPath(), $restored->getPath());
            $this->assertEquals($original->getModule(), $restored->getModule());
            $this->assertEquals($original->getLocaleId(), $restored->getLocaleId());
            $this->assertEquals($original->getAllowedMethods(), $restored->getAllowedMethods());
        }

        public function testRouteWithoutMethodsAllowsAll(): void
        {
            $route = new Route(['path' => '/', 'module' => 'index.phtml']);
            $this->assertEquals(['*'], $route->getAllowedMethods());
        }

        // RouterConfiguration tests

        public function testRouterConfigurationBasic(): void
        {
            $data = $this->createMinimalConfigData();
            $router = new RouterConfiguration($data['router']);

            $this->assertEquals('', $router->getBasePath());
            $this->assertEmpty($router->getResponseHandlers());
            $this->assertCount(2, $router->getRoutes());
        }

        public function testRouterConfigurationGetRoute(): void
        {
            $data = $this->createMinimalConfigData();
            $router = new RouterConfiguration($data['router']);

            $home = $router->getRoute('/');
            $this->assertNotNull($home);
            $this->assertEquals('index.phtml', $home->getModule());

            $about = $router->getRoute('/about');
            $this->assertNotNull($about);
            $this->assertEquals('about.phtml', $about->getModule());

            $this->assertNull($router->getRoute('/nonexistent'));
        }

        public function testRouterConfigurationGetRouteById(): void
        {
            $data = $this->createMinimalConfigData();
            $router = new RouterConfiguration($data['router']);

            $home = $router->getRouteById('home');
            $this->assertNotNull($home);
            $this->assertEquals('/', $home->getPath());

            $this->assertNull($router->getRouteById('nonexistent'));
        }

        public function testRouterConfigurationResponseHandlers(): void
        {
            $data = $this->createFullConfigData();
            $router = new RouterConfiguration($data['router']);

            $this->assertEquals('errors/404.phtml', $router->getResponseHandler(404));
            $this->assertEquals('errors/500.phtml', $router->getResponseHandler(ResponseCode::INTERNAL_SERVER_ERROR));
            $this->assertNull($router->getResponseHandler(403));
        }

        public function testRouterConfigurationToArrayRoundTrip(): void
        {
            $data = $this->createFullConfigData();
            $router = new RouterConfiguration($data['router']);
            $array = $router->toArray();

            $this->assertArrayNotHasKey('base_url', $array);
            $this->assertEquals('/app', $array['base_path']);
            $this->assertCount(2, $array['routes']);
        }

        // Section tests

        public function testSectionWithLocale(): void
        {
            $section = new Section('header', [
                'module' => 'sections/header.phtml',
                'locale_id' => 'header',
            ]);

            $this->assertEquals('header', $section->getName());
            $this->assertEquals('sections/header.phtml', $section->getModule());
            $this->assertEquals('header', $section->getLocaleId());
        }

        public function testSectionWithoutLocale(): void
        {
            $section = new Section('footer', [
                'module' => 'sections/footer.phtml',
            ]);

            $this->assertEquals('footer', $section->getName());
            $this->assertNull($section->getLocaleId());
        }

        public function testSectionToArrayRoundTrip(): void
        {
            $original = new Section('sidebar', [
                'module' => 'sections/sidebar.phtml',
                'locale_id' => 'sidebar',
            ]);

            $array = $original->toArray();
            $this->assertEquals('sections/sidebar.phtml', $array['module']);
            $this->assertEquals('sidebar', $array['locale_id']);
        }

        // WebConfiguration tests

        public function testWebConfigurationMinimal(): void
        {
            $data = $this->createMinimalConfigData();
            $config = new WebConfiguration($data);

            $this->assertInstanceOf(ApplicationConfiguration::class, $config->getApplication());
            $this->assertInstanceOf(RouterConfiguration::class, $config->getRouter());
            $this->assertNull($config->getLocales());
            $this->assertEmpty($config->getSections());
        }

        public function testWebConfigurationFull(): void
        {
            $data = $this->createFullConfigData();
            $config = new WebConfiguration($data);

            $this->assertEquals('FullApp', $config->getApplication()->getName());
            $this->assertNotNull($config->getLocales());
            $this->assertCount(2, $config->getLocales());
            $this->assertCount(2, $config->getSections());
            $this->assertNotNull($config->getSection('header'));
            $this->assertNotNull($config->getSection('footer'));
            $this->assertNull($config->getSection('nonexistent'));
        }

        public function testWebConfigurationToArrayRoundTrip(): void
        {
            $data = $this->createFullConfigData();
            $config = new WebConfiguration($data);
            $array = $config->toArray();

            $restored = WebConfiguration::fromArray($array);
            $this->assertEquals($config->getApplication()->getName(), $restored->getApplication()->getName());
            $this->assertEquals($config->getRouter()->getBasePath(), $restored->getRouter()->getBasePath());
            $this->assertEquals($config->getLocales(), $restored->getLocales());
            $this->assertCount(2, $restored->getSections());
        }

        // RouteResult tests

        public function testRouteResultWithRoute(): void
        {
            $route = new Route(['path' => '/', 'module' => 'index.phtml']);
            $result = new RouteResult('/path/to/module.phtml', $route);

            $this->assertEquals('/path/to/module.phtml', $result->getModule());
            $this->assertSame($route, $result->getRoute());
        }

        public function testRouteResultWithNulls(): void
        {
            $result = new RouteResult(null, null);

            $this->assertNull($result->getModule());
            $this->assertNull($result->getRoute());
        }
    }
