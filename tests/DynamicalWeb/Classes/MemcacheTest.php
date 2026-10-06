<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Tests\Fixtures\MemcachedServer;
    use Memcached;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;

    class MemcacheTest extends TestCase
    {
        private const array ENV_KEYS = ['MEMCACHED_ENABLED', 'MEMCACHED_HOST', 'MEMCACHED_PORT', 'MEMCACHED_KEY_PREFIX'];

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
            self::resetMemcache();

            foreach (self::$originalEnv as $key => $value)
            {
                putenv($value === false ? $key : $key . '=' . $value);
            }
        }

        protected function setUp(): void
        {
            foreach (self::ENV_KEYS as $key)
            {
                putenv($key);
            }

            self::resetMemcache();
        }

        private static function resetMemcache(): void
        {
            $reflection = new ReflectionClass(Memcache::class);
            $reflection->getProperty('memcached')->setValue(null, null);
            $reflection->getProperty('initialized')->setValue(null, false);
            $reflection->getProperty('keyPrefix')->setValue(null, 'dw_app_');
        }

        private function enableMemcache(?string $prefix = null): void
        {
            if (self::$port === null)
            {
                $this->markTestSkipped('The memcached extension and server binary are required');
            }

            putenv('MEMCACHED_ENABLED=1');
            putenv('MEMCACHED_HOST=127.0.0.1');
            putenv('MEMCACHED_PORT=' . self::$port);
            if ($prefix !== null)
            {
                putenv('MEMCACHED_KEY_PREFIX=' . $prefix);
            }

            self::resetMemcache();
            $this->assertTrue(Memcache::isAvailable());
            Memcache::getClient()->flush();
        }

        // Disabled behaviour

        public function testUnavailableWhenEnvNotSet(): void
        {
            $this->assertFalse(Memcache::isAvailable());
            $this->assertNull(Memcache::getClient());
        }

        public function testUnavailableWhenEnvDisabled(): void
        {
            foreach (['0', 'false', 'no', 'off', 'garbage'] as $value)
            {
                putenv('MEMCACHED_ENABLED=' . $value);
                self::resetMemcache();
                $this->assertFalse(Memcache::isAvailable(), "MEMCACHED_ENABLED=$value should be disabled");
            }
        }

        public function testEnabledValuesAreCaseInsensitive(): void
        {
            if (!class_exists(Memcached::class))
            {
                $this->markTestSkipped('The memcached extension is required');
            }

            foreach (['1', 'true', 'TRUE', 'Yes', 'on'] as $value)
            {
                putenv('MEMCACHED_ENABLED=' . $value);
                self::resetMemcache();
                $this->assertTrue(Memcache::isAvailable(), "MEMCACHED_ENABLED=$value should be enabled");
            }
        }

        public function testMethodsNoOpWhenUnavailable(): void
        {
            $success = true;
            $this->assertFalse(Memcache::fetch('key', $success));
            $this->assertFalse($success);
            $this->assertFalse(Memcache::store('key', 'value'));
            $this->assertFalse(Memcache::add('key', 'value'));
            $this->assertFalse(Memcache::delete('key'));
            $this->assertFalse(Memcache::exists('key'));
            $this->assertSame([], Memcache::fetchMultiple(['a', 'b']));
            $this->assertFalse(Memcache::storeMultiple(['a' => 1]));
            $this->assertFalse(Memcache::increment('counter'));
            $this->assertFalse(Memcache::decrement('counter'));
            $this->assertFalse(Memcache::touch('key', 10));
            $this->assertFalse(Memcache::stats());
        }

        public function testRememberInvokesCallbackEveryTimeWhenUnavailable(): void
        {
            $calls = 0;
            $callback = function () use (&$calls) { return ++$calls; };

            $this->assertSame(1, Memcache::remember('key', 60, $callback));
            $this->assertSame(2, Memcache::remember('key', 60, $callback));
        }

        public function testDefaultKeyPrefix(): void
        {
            $this->assertSame('dw_app_', Memcache::getKeyPrefix());
        }

        // Enabled behaviour

        public function testStoreAndFetch(): void
        {
            $this->enableMemcache();

            $this->assertTrue(Memcache::store('greeting', 'hello'));
            $success = false;
            $this->assertSame('hello', Memcache::fetch('greeting', $success));
            $this->assertTrue($success);
        }

        public function testStoresComplexValues(): void
        {
            $this->enableMemcache();

            $value = ['list' => [1, 2, 3], 'nested' => ['flag' => true, 'name' => 'kernel'], 'float' => 1.5];
            $this->assertTrue(Memcache::store('complex', $value));
            $this->assertSame($value, Memcache::fetch('complex'));
        }

        public function testFetchMissReportsFailure(): void
        {
            $this->enableMemcache();

            $success = true;
            $this->assertFalse(Memcache::fetch('missing', $success));
            $this->assertFalse($success);
        }

        public function testStoredFalseIsDistinguishableFromMiss(): void
        {
            $this->enableMemcache();

            $this->assertTrue(Memcache::store('false_value', false));
            $success = false;
            $this->assertFalse(Memcache::fetch('false_value', $success));
            $this->assertTrue($success);
            $this->assertTrue(Memcache::exists('false_value'));
        }

        public function testExistsAndDelete(): void
        {
            $this->enableMemcache();

            $this->assertFalse(Memcache::exists('key'));
            Memcache::store('key', 'value');
            $this->assertTrue(Memcache::exists('key'));
            $this->assertTrue(Memcache::delete('key'));
            $this->assertFalse(Memcache::exists('key'));
            $this->assertFalse(Memcache::delete('key'));
        }

        public function testAddOnlyStoresWhenMissing(): void
        {
            $this->enableMemcache();

            $this->assertTrue(Memcache::add('key', 'first'));
            $this->assertFalse(Memcache::add('key', 'second'));
            $this->assertSame('first', Memcache::fetch('key'));
        }

        public function testTtlExpiry(): void
        {
            $this->enableMemcache();

            Memcache::store('short_lived', 'value', 1);
            $this->assertTrue(Memcache::exists('short_lived'));
            sleep(2);
            $this->assertFalse(Memcache::exists('short_lived'));
        }

        public function testTouchExtendsTtl(): void
        {
            $this->enableMemcache();

            $this->assertFalse(Memcache::touch('missing', 60));

            Memcache::store('touched', 'value', 1);
            $this->assertTrue(Memcache::touch('touched', 60));
            sleep(2);
            $this->assertSame('value', Memcache::fetch('touched'));
        }

        public function testMultipleOperations(): void
        {
            $this->enableMemcache();

            $this->assertTrue(Memcache::storeMultiple(['a' => 1, 'b' => 'two', 'c' => [3]]));
            $result = Memcache::fetchMultiple(['a', 'b', 'c', 'missing']);

            ksort($result);
            $this->assertSame(['a' => 1, 'b' => 'two', 'c' => [3]], $result);
            $this->assertSame([], Memcache::fetchMultiple([]));
        }

        public function testIncrementInitializesAndIncrements(): void
        {
            $this->enableMemcache();

            $this->assertSame(0, Memcache::increment('counter'));
            $this->assertSame(1, Memcache::increment('counter'));
            $this->assertSame(6, Memcache::increment('counter', 5));
            $this->assertSame(10, Memcache::increment('other', 1, 10));
        }

        public function testDecrementInitializesAndNeverGoesBelowZero(): void
        {
            $this->enableMemcache();

            $this->assertSame(5, Memcache::decrement('counter', 1, 5));
            $this->assertSame(3, Memcache::decrement('counter', 2));
            $this->assertSame(0, Memcache::decrement('counter', 10));
        }

        public function testRememberCachesCallbackResult(): void
        {
            $this->enableMemcache();

            $calls = 0;
            $callback = function () use (&$calls) { $calls++; return ['compiled' => 'kernel']; };

            $this->assertSame(['compiled' => 'kernel'], Memcache::remember('kernel', 60, $callback));
            $this->assertSame(['compiled' => 'kernel'], Memcache::remember('kernel', 60, $callback));
            $this->assertSame(1, $calls);
        }

        public function testKeysAreNamespacedWithDefaultPrefix(): void
        {
            $this->enableMemcache();

            Memcache::store('namespaced', 'value');

            $raw = new Memcached();
            $raw->addServer('127.0.0.1', self::$port);
            $this->assertSame('value', $raw->get('dw_app_namespaced'));
            $this->assertFalse($raw->get('namespaced'));
        }

        public function testCustomKeyPrefix(): void
        {
            $this->enableMemcache('federation_');

            $this->assertSame('federation_', Memcache::getKeyPrefix());
            Memcache::store('kernel', 'value');

            $raw = new Memcached();
            $raw->addServer('127.0.0.1', self::$port);
            $this->assertSame('value', $raw->get('federation_kernel'));
            $this->assertFalse($raw->get('dw_app_kernel'));
        }

        public function testDoesNotCollideWithCookieSessions(): void
        {
            $this->enableMemcache();

            $raw = new Memcached();
            $raw->addServer('127.0.0.1', self::$port);
            $raw->set('dw_sess_abc', ['session' => true]);

            $this->assertFalse(Memcache::exists('dw_sess_abc'));
            Memcache::store('dw_sess_abc', 'app value');
            $this->assertSame(['session' => true], $raw->get('dw_sess_abc'));
        }

        public function testStatsReturnsServerStatistics(): void
        {
            $this->enableMemcache();

            $stats = Memcache::stats();
            $this->assertIsArray($stats);
            $this->assertArrayHasKey('127.0.0.1:' . self::$port, $stats);
        }

        public function testClientIsReused(): void
        {
            $this->enableMemcache();

            $this->assertSame(Memcache::getClient(), Memcache::getClient());
        }
    }
