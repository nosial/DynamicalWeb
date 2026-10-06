<?php

    namespace DynamicalWeb\Html;

    use DynamicalWeb\Objects\Locale;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\Tests\Fixtures\FakeMemcached;
    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;
    use DynamicalWeb\WebSession;
    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    class FunctionsTest extends TestCase
    {
        private static array $originalEnv = [];

        public static function setUpBeforeClass(): void
        {
            foreach (['MEMCACHED_ENABLED'] as $key)
            {
                self::$originalEnv[$key] = getenv($key);
            }
        }

        public static function tearDownAfterClass(): void
        {
            foreach (self::$originalEnv as $key => $value)
            {
                putenv($value === false ? $key : $key . '=' . $value);
            }
        }

        protected function setUp(): void
        {
            putenv('MEMCACHED_ENABLED');
        }

        protected function tearDown(): void
        {
            WebSessionFixture::reset();
        }

        private function installLocale(?string $routeLocaleId = 'reports'): void
        {
            $locale = new Locale('en', [
                'global' => ['site_name' => 'Federation', 'welcome' => 'Hello {name}'],
                'reports' => ['page_title' => 'Reports <list>', 'count' => '{count} reports'],
                'navbar' => ['home' => 'Home'],
            ]);
            $route = new Route(['id' => 'reports', 'path' => '/reports', 'module' => 'reports.phtml', 'locale_id' => $routeLocaleId]);
            WebSessionFixture::install(WebSessionFixture::makeRequest(), null, $route, $locale);
        }

        private function capture(callable $callback): string
        {
            ob_start();
            try
            {
                $callback();
            }
            finally
            {
                $output = ob_get_clean();
            }

            return $output;
        }

        // getl / hasl

        public function testGetlResolvesFromRouteLocaleWithReplacements(): void
        {
            $this->installLocale();

            $this->assertSame('Reports <list>', Functions::getl('page_title'));
            $this->assertSame('3 reports', Functions::getl('count', ['count' => 3]));
        }

        public function testGetlFallsBackToGlobalSection(): void
        {
            $this->installLocale();

            $this->assertSame('Hello Ada', Functions::getl('welcome', ['name' => 'Ada']));
        }

        public function testGetlUsesActiveSection(): void
        {
            $this->installLocale();
            Functions::loadLocalization('navbar');

            $this->assertSame('navbar', Functions::getActiveLocaleId());
            $this->assertSame('Home', Functions::getl('home'));
        }

        public function testGetlThrowsForMissingKeyLikePrintl(): void
        {
            $this->installLocale();

            $this->expectException(RuntimeException::class);
            Functions::getl('missing_key');
        }

        public function testGetlReturnsDefaultForMissingKey(): void
        {
            $this->installLocale();

            $this->assertSame('missing_key', Functions::getl('missing_key', default: 'missing_key'));
        }

        public function testGetlWithoutLocaleThrowsUnlessDefaultGiven(): void
        {
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $this->assertSame('fallback', Functions::getl('page_title', default: 'fallback'));
            $this->expectException(RuntimeException::class);
            Functions::getl('page_title');
        }

        public function testHasl(): void
        {
            $this->installLocale();

            $this->assertTrue(Functions::hasl('page_title'));
            $this->assertTrue(Functions::hasl('site_name'));
            $this->assertFalse(Functions::hasl('missing_key'));
        }

        public function testPrintlStillEscapesAndPrints(): void
        {
            $this->installLocale();

            $this->assertSame('Reports &lt;list&gt;', $this->capture(fn() => Functions::printl('page_title')));
            $this->assertSame('Reports <list>', $this->capture(fn() => Functions::printl('page_title', escape: false)));
            $this->assertSame(Functions::getl('count', ['count' => 2]), $this->capture(fn() => Functions::printl('count', ['count' => 2])));
        }

        public function testPrintlStillThrowsForMissingKey(): void
        {
            $this->installLocale();

            $this->expectException(RuntimeException::class);
            $this->capture(fn() => Functions::printl('missing_key'));
        }

        // Absolute route URLs

        private function installRoutes(bool $secure, string $host): void
        {
            $instance = WebSessionFixture::makeInstance(['router' => ['base_path' => '/', 'routes' => [
                ['id' => 'report_detail', 'path' => '/reports/{report_uuid}', 'module' => 'report.phtml'],
            ]]]);
            WebSessionFixture::install(WebSessionFixture::makeRequest(['secure' => $secure, 'host' => $host]), null, null, null, $instance);
        }

        public function testGetAbsoluteRouteUrl(): void
        {
            $this->installRoutes(true, 'federation.example.com');

            $this->assertSame('/reports/abc', Functions::getRouteUrl('report_detail', ['report_uuid' => 'abc']));
            $this->assertSame(
                'https://federation.example.com/reports/abc?tab=evidence',
                Functions::getAbsoluteRouteUrl('report_detail', ['report_uuid' => 'abc'], ['tab' => 'evidence'])
            );
        }

        public function testGetAbsoluteRouteUrlUsesHttpAndPort(): void
        {
            $this->installRoutes(false, 'localhost:8080');

            $this->assertSame('http://localhost:8080/reports/x', Functions::getAbsoluteRouteUrl('report_detail', ['report_uuid' => 'x']));
        }

        // CSRF helpers

        public function testCsrfHelpersPrintNothingWithoutSessions(): void
        {
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $this->assertSame('', $this->capture(fn() => Functions::csrfField()));
            $this->assertSame('', $this->capture(fn() => Functions::csrfMeta(true)));
        }

        public function testCsrfFieldAndMetaCarryTheSessionToken(): void
        {
            putenv('MEMCACHED_ENABLED=1');
            FakeMemcached::reset();
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $field = $this->capture(fn() => Functions::csrfField());
            $token = WebSession::getCsrfToken();
            $this->assertSame('<input type="hidden" name="csrf_token" value="' . $token . '">', $field);

            $meta = $this->capture(fn() => Functions::csrfMeta());
            $this->assertSame('<meta name="csrf-token" content="' . $token . '">', $meta);

            $withScript = $this->capture(fn() => Functions::csrfMeta(true));
            $this->assertStringStartsWith($meta, $withScript);
            $this->assertStringContainsString('"X-CSRF-Token"', $withScript);
            $this->assertStringContainsString('XMLHttpRequest.prototype.open', $withScript);
            $this->assertStringContainsString('window.fetch', $withScript);
        }
    }
