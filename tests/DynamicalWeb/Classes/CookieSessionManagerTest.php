<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Enums\RequestMethod;
    use DynamicalWeb\Objects\CookieSession;
    use DynamicalWeb\Objects\Response;
    use DynamicalWeb\Tests\Fixtures\MemcachedServer;
    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;
    use DynamicalWeb\WebSession;
    use PHPUnit\Framework\TestCase;

    class CookieSessionManagerTest extends TestCase
    {
        private const array ENV_KEYS = ['MEMCACHED_ENABLED', 'MEMCACHED_HOST', 'MEMCACHED_PORT', 'MEMCACHED_SESSION_TTL', 'MEMCACHED_SESSION_SLIDING', 'MEMCACHED_SESSION_BIND_IP'];

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

        protected function setUp(): void
        {
            if (self::$port === null)
            {
                $this->markTestSkipped('The memcached extension and server binary are required');
            }

            foreach (self::ENV_KEYS as $key)
            {
                putenv($key);
            }

            putenv('MEMCACHED_ENABLED=1');
            putenv('MEMCACHED_HOST=127.0.0.1');
            putenv('MEMCACHED_PORT=' . self::$port);
            MemcachedServer::flush();
            WebSessionFixture::install(WebSessionFixture::makeRequest());
        }

        protected function tearDown(): void
        {
            WebSessionFixture::reset();
        }

        private function storedSession(string $sessionId): array|false
        {
            return MemcachedServer::client()->get('dw_sess_' . $sessionId);
        }

        // SameSite

        public function testCreateSessionDefaultsToLaxSameSite(): void
        {
            $session = WebSession::createCookieSession(['user' => 1]);

            $cookie = WebSession::getResponse()->getCookie('web_session');
            $this->assertNotNull($cookie);
            $this->assertSame($session->getSessionId(), $cookie->getValue());
            $this->assertSame('Lax', $cookie->getSameSite());
            $this->assertTrue($cookie->isHttpOnly());
        }

        public function testCreateSessionAppliesRequestedSameSite(): void
        {
            WebSession::createCookieSession([], 'strict_session', sameSite: 'Strict');

            $this->assertSame('Strict', WebSession::getResponse()->getCookie('strict_session')->getSameSite());
        }

        public function testCreateSessionRecordsCookieOptions(): void
        {
            $session = WebSession::createCookieSession([], 'scoped', '/app', 'example.com', true, false, 'Strict');

            $stored = CookieSession::fromArray($this->storedSession($session->getSessionId()));
            $this->assertSame([
                'path' => '/app',
                'domain' => 'example.com',
                'secure' => true,
                'http_only' => false,
                'same_site' => 'Strict',
            ], $stored->getCookieOptions());
        }

        // Sliding expiration

        public function testCookieIsNotRenewedByDefault(): void
        {
            $created = WebSession::getResponse();
            WebSession::createCookieSession(['user' => 1]);

            $next = WebSessionFixture::nextRequest($created);
            $this->assertNotNull(WebSession::getCookieSession());
            $this->assertFalse(WebSession::getCookieSessionManager()->isSlidingExpiration());
            $this->assertNull($next->getCookie('web_session'));
        }

        public function testSlidingExpirationRenewsCookieWithOriginalAttributes(): void
        {
            putenv('MEMCACHED_SESSION_SLIDING=1');
            putenv('MEMCACHED_SESSION_TTL=600');
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession([], 'scoped', '/app', 'example.com', true, true, 'Strict');

            $next = WebSessionFixture::nextRequest($created);
            $this->assertTrue(WebSession::getCookieSessionManager()->isSlidingExpiration());
            $this->assertNotNull(WebSession::getCookieSession('scoped'));

            $cookie = $next->getCookie('scoped');
            $this->assertNotNull($cookie);
            $this->assertSame($session->getSessionId(), $cookie->getValue());
            $this->assertSame('/app', $cookie->getPath());
            $this->assertSame('example.com', $cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertSame('Strict', $cookie->getSameSite());
            $this->assertEqualsWithDelta(time() + 600, $cookie->getExpires(), 2);
        }

        public function testSlidingExpirationFallsBackForSessionsWithoutRecordedOptions(): void
        {
            putenv('MEMCACHED_SESSION_SLIDING=1');
            $legacy = new CookieSession('legacy-session', ['user' => 1]);
            MemcachedServer::client()->set('dw_sess_legacy-session', $legacy->toArray());

            $response = WebSessionFixture::install(WebSessionFixture::makeRequest(['cookies' => ['web_session' => 'legacy-session']]));
            $this->assertNotNull(WebSession::getCookieSession());

            $cookie = $response->getCookie('web_session');
            $this->assertSame('/', $cookie->getPath());
            $this->assertSame('Lax', $cookie->getSameSite());
            $this->assertTrue($cookie->isHttpOnly());
        }

        // Fingerprints

        public function testIpChangeInvalidatesSessionByDefault(): void
        {
            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession(['user' => 1]);
            $this->assertTrue(WebSession::getCookieSessionManager()->isIpBound());

            $next = WebSessionFixture::nextRequest($created, ['client_ip' => '198.51.100.7']);
            $this->assertNull(WebSession::getCookieSession());
            $this->assertFalse($this->storedSession($session->getSessionId()));
            $this->assertLessThan(time(), $next->getCookie('web_session')->getExpires());
        }

        public function testIpChangeKeepsSessionWhenNotIpBound(): void
        {
            putenv('MEMCACHED_SESSION_BIND_IP=0');
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $created = WebSession::getResponse();
            WebSession::createCookieSession(['user' => 1]);

            WebSessionFixture::nextRequest($created, ['client_ip' => '198.51.100.7']);
            $this->assertFalse(WebSession::getCookieSessionManager()->isIpBound());
            $this->assertSame(1, WebSession::getCookieSession()?->get('user'));
        }

        public function testUserAgentChangeInvalidatesSessionWhenNotIpBound(): void
        {
            putenv('MEMCACHED_SESSION_BIND_IP=0');
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession(['user' => 1]);

            WebSessionFixture::nextRequest($created, ['user_agent' => 'OtherAgent/2.0']);
            $this->assertNull(WebSession::getCookieSession());
            $this->assertFalse($this->storedSession($session->getSessionId()));
        }

        public function testIpBoundSessionIsMigratedWhenIpBindingIsDisabled(): void
        {
            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession(['user' => 1]);
            $originalFingerprint = $session->getFingerprint();

            putenv('MEMCACHED_SESSION_BIND_IP=0');
            WebSessionFixture::nextRequest($created);
            $this->assertSame(1, WebSession::getCookieSession()?->get('user'));

            $migrated = CookieSession::fromArray($this->storedSession($session->getSessionId()));
            $this->assertNotSame($originalFingerprint, $migrated->getFingerprint());

            // After migration the session also survives an IP change
            WebSessionFixture::install(WebSessionFixture::makeRequest(['cookies' => ['web_session' => $session->getSessionId()], 'client_ip' => '198.51.100.7']));
            $this->assertSame(1, WebSession::getCookieSession()?->get('user'));
        }

        public function testWebSocketFingerprintMismatchKeepsBrowserSession(): void
        {
            $created = WebSession::getResponse();
            $session = WebSession::createCookieSession(['user' => 1]);

            // WebSocket requests arrive through the local bridge, from a different address
            $next = WebSessionFixture::nextRequest($created, ['method' => RequestMethod::WEBSOCKET, 'client_ip' => '127.0.0.1']);
            $this->assertNull(WebSession::getCookieSession());
            $this->assertNotFalse($this->storedSession($session->getSessionId()));
            $this->assertNull($next->getCookie('web_session'));

            // The browser's next HTTP request still has its session
            WebSessionFixture::install(WebSessionFixture::makeRequest(['cookies' => ['web_session' => $session->getSessionId()]]));
            $this->assertSame(1, WebSession::getCookieSession()?->get('user'));
        }

        public function testWebSocketRequestsDoNotRenewCookie(): void
        {
            putenv('MEMCACHED_SESSION_SLIDING=1');
            putenv('MEMCACHED_SESSION_BIND_IP=0');
            WebSessionFixture::install(WebSessionFixture::makeRequest());

            $created = WebSession::getResponse();
            WebSession::createCookieSession(['user' => 1]);

            $next = WebSessionFixture::nextRequest($created, ['method' => RequestMethod::WEBSOCKET]);
            $this->assertSame(1, WebSession::getCookieSession()?->get('user'));
            $this->assertNull($next->getCookie('web_session'));
        }
    }
