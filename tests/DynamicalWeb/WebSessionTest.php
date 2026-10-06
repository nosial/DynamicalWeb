<?php

    namespace DynamicalWeb;

    use DynamicalWeb\Enums\RequestMethod;
    use DynamicalWeb\Tests\Fixtures\MemcachedServer;
    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;
    use PHPUnit\Framework\TestCase;

    class WebSessionTest extends TestCase
    {
        private const array ENV_KEYS = ['MEMCACHED_ENABLED', 'MEMCACHED_HOST', 'MEMCACHED_PORT'];

        private static ?int $port = null;
        private static array $originalEnv = [];

        public static function setUpBeforeClass(): void
        {
            foreach (self::ENV_KEYS as $key)
            {
                self::$originalEnv[$key] = getenv($key);
            }

            self::$port = MemcachedServer::start();
        }

        public static function tearDownAfterClass(): void
        {
            WebSessionFixture::reset();
            foreach (self::$originalEnv as $key => $value)
            {
                putenv($value === false ? $key : $key . '=' . $value);
            }
        }

        protected function tearDown(): void
        {
            WebSessionFixture::reset();
        }

        private function enableSessions(array $requestOptions = []): void
        {
            if (self::$port === null)
            {
                $this->markTestSkipped('The memcached extension and server binary are required');
            }

            putenv('MEMCACHED_ENABLED=1');
            putenv('MEMCACHED_HOST=127.0.0.1');
            putenv('MEMCACHED_PORT=' . self::$port);
            MemcachedServer::flush();
            WebSessionFixture::install(WebSessionFixture::makeRequest($requestOptions));
        }

        private function disableSessions(): void
        {
            putenv('MEMCACHED_ENABLED');
            WebSessionFixture::install(WebSessionFixture::makeRequest());
        }

        // Shared session instance

        public function testGetCookieSessionReturnsSameInstanceWithinRequest(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();
            WebSession::createCookieSession(['count' => 0]);

            WebSessionFixture::nextRequest($created);
            $first = WebSession::getCookieSession();
            $second = WebSession::getCookieSession();
            $this->assertNotNull($first);
            $this->assertSame($first, $second);
        }

        public function testChangesFromOneCallerAreNotLostWhenAnotherSaves(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession();

            WebSessionFixture::nextRequest($created);
            $pageCopy = WebSession::getCookieSession();
            WebSession::flash('notice', 'saved');      // writes through its own lookup
            $pageCopy->set('dark_mode', true);
            WebSession::saveCookieSession($pageCopy);   // must not drop the flash

            WebSessionFixture::install(WebSessionFixture::makeRequest(['cookies' => ['web_session' => $session->getSessionId()]]));
            $this->assertTrue(WebSession::getCookieSession()->get('dark_mode'));
            $this->assertSame('saved', WebSession::getFlash('notice'));
        }

        public function testHasCookieSessionUnchangedForSessionCreatedThisRequest(): void
        {
            $this->enableSessions();
            WebSession::createCookieSession();

            // As before, has/get only report sessions sent by the browser
            $this->assertFalse(WebSession::hasCookieSession());
            $this->assertNull(WebSession::getCookieSession());
        }

        public function testDestroyClearsCachedSession(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();
            WebSession::createCookieSession();

            WebSessionFixture::nextRequest($created);
            $this->assertNotNull(WebSession::getCookieSession());
            $this->assertTrue(WebSession::destroyCookieSession());
            $this->assertNull(WebSession::getCookieSession());
        }

        // CSRF

        public function testCsrfTokenIsCreatedOnceAndPersisted(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();

            $token = WebSession::getCsrfToken();
            $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
            $this->assertSame($token, WebSession::getCsrfToken());

            WebSessionFixture::nextRequest($created);
            $this->assertSame($token, WebSession::getCsrfToken());
        }

        public function testCsrfTokenReusesSessionCreatedThisRequest(): void
        {
            $this->enableSessions();
            $session = WebSession::createCookieSession(['user' => 1]);

            WebSession::getCsrfToken();
            $this->assertCount(1, WebSession::getResponse()->getCookies());
            $this->assertSame($session->getSessionId(), WebSession::getResponse()->getCookie('web_session')->getValue());
        }

        public function testVerifyCsrfTokenFromFormFieldHeaderAndArgument(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();
            $token = WebSession::getCsrfToken();

            WebSessionFixture::nextRequest($created, ['method' => RequestMethod::POST, 'form' => ['csrf_token' => $token]]);
            $this->assertTrue(WebSession::verifyCsrfToken());

            WebSessionFixture::nextRequest($created, ['method' => RequestMethod::POST, 'headers' => ['X-CSRF-Token' => $token]]);
            $this->assertTrue(WebSession::verifyCsrfToken());

            WebSessionFixture::nextRequest($created, ['method' => RequestMethod::POST]);
            $this->assertFalse(WebSession::verifyCsrfToken());
            $this->assertTrue(WebSession::verifyCsrfToken($token));
        }

        public function testVerifyCsrfTokenRejectsWrongOrNonStringTokens(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();
            WebSession::getCsrfToken();

            WebSessionFixture::nextRequest($created, ['method' => RequestMethod::POST, 'form' => ['csrf_token' => 'wrong']]);
            $this->assertFalse(WebSession::verifyCsrfToken());

            WebSessionFixture::nextRequest($created, ['method' => RequestMethod::POST, 'form' => ['csrf_token' => ['array']]]);
            $this->assertFalse(WebSession::verifyCsrfToken());
        }

        public function testVerifyCsrfTokenFailsWithoutSessionAndDoesNotCreateOne(): void
        {
            $this->enableSessions(['method' => RequestMethod::POST, 'form' => ['csrf_token' => 'anything']]);

            $this->assertFalse(WebSession::verifyCsrfToken());
            $this->assertCount(0, WebSession::getResponse()->getCookies());
        }

        public function testCsrfWithSessionsDisabled(): void
        {
            $this->disableSessions();

            $this->assertNull(WebSession::getCsrfToken());
            $this->assertFalse(WebSession::verifyCsrfToken('anything'));
        }

        // Flash

        public function testFlashIsReadOnceOnTheNextRequest(): void
        {
            $this->enableSessions();
            $created = WebSession::getResponse();

            $this->assertTrue(WebSession::flash('success', 'report_closed'));
            $this->assertTrue(WebSession::flash('details', ['id' => 7]));

            WebSessionFixture::nextRequest($created);
            $this->assertTrue(WebSession::hasFlash('success'));
            $this->assertSame('report_closed', WebSession::getFlash('success'));
            $this->assertFalse(WebSession::hasFlash('success'));
            $this->assertSame('fallback', WebSession::getFlash('success', 'fallback'));

            // Consumption is persisted, and other keys are kept
            WebSessionFixture::nextRequest($created);
            $this->assertNull(WebSession::getFlash('success'));
            $this->assertSame(['id' => 7], WebSession::getFlash('details'));

            WebSessionFixture::nextRequest($created);
            $this->assertNotContains('_dw_flash', array_keys(WebSession::getCookieSession()->getData()));
        }

        public function testFlashWithSessionsDisabled(): void
        {
            $this->disableSessions();

            $this->assertFalse(WebSession::flash('success', 'x'));
            $this->assertFalse(WebSession::hasFlash('success'));
            $this->assertSame('default', WebSession::getFlash('success', 'default'));
        }
    }
