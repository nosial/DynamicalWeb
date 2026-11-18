<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Tests\Fixtures\FakeMemcached;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;

    class MemcacheTest extends TestCase
    {
        private const array ENV_KEYS = ['MEMCACHED_ENABLED', 'MEMCACHED_KEY_PREFIX'];

        private static array $originalEnv = [];

        public static function setUpBeforeClass(): void
        {
            foreach (self::ENV_KEYS as $key)
            {
                self::$originalEnv[$key] = getenv($key);
            }
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
            $reflection->getProperty('useApcu')->setValue(null, false);
            $reflection->getProperty('keyPrefix')->setValue(null, 'dw_app_');
        }

        private function enableMemcache(?string $prefix = null): void
        {
            putenv('MEMCACHED_ENABLED=1');
            if ($prefix !== null)
            {
                putenv('MEMCACHED_KEY_PREFIX=' . $prefix);
            }

            self::resetMemcache();
            FakeMemcached::reset();
            FakeMemcached::attachToMemcache();
            $this->assertTrue(Memcache::isAvailable());
        }

        // Disabled behaviour

        public function testUnavailableWhenEnvNotSet(): void
        {
            $this->requireNoApcu();
            $this->assertFalse(Memcache::isAvailable());
            $this->assertNull(Memcache::getClient());
        }

        public function testUnavailableWhenEnvDisabled(): void
        {
            $this->requireNoApcu();
            foreach (['0', 'false', 'no', 'off', 'garbage'] as $value)
            {
                putenv('MEMCACHED_ENABLED=' . $value);
                self::resetMemcache();
                $this->assertFalse(Memcache::isAvailable(), "MEMCACHED_ENABLED=$value should be disabled");
            }
        }

        public function testEnabledValuesAreCaseInsensitive(): void
        {
            foreach (['1', 'true', 'TRUE', 'Yes', 'on'] as $value)
            {
                putenv('MEMCACHED_ENABLED=' . $value);
                self::resetMemcache();
                $this->assertTrue(Memcache::isAvailable(), "MEMCACHED_ENABLED=$value should be enabled");
            }
        }

        public function testMethodsNoOpWhenUnavailable(): void
        {
            $this->requireNoApcu();
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
            $this->requireNoApcu();
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
            FakeMemcached::advance(2);
            $this->assertFalse(Memcache::exists('short_lived'));
        }

        public function testTouchExtendsTtl(): void
        {
            $this->enableMemcache();

            $this->assertFalse(Memcache::touch('missing', 60));

            Memcache::store('touched', 'value', 1);
            $this->assertTrue(Memcache::touch('touched', 60));
            FakeMemcached::advance(2);
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

            $raw = new FakeMemcached();
            $this->assertSame('value', $raw->get('dw_app_namespaced'));
            $this->assertFalse($raw->get('namespaced'));
        }

        public function testCustomKeyPrefix(): void
        {
            $this->enableMemcache('federation_');

            $this->assertSame('federation_', Memcache::getKeyPrefix());
            Memcache::store('kernel', 'value');

            $raw = new FakeMemcached();
            $this->assertSame('value', $raw->get('federation_kernel'));
            $this->assertFalse($raw->get('dw_app_kernel'));
        }

        public function testDoesNotCollideWithCookieSessions(): void
        {
            $this->enableMemcache();

            $raw = new FakeMemcached();
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
            $this->assertNotEmpty($stats);
        }

        public function testClientIsReused(): void
        {
            $this->enableMemcache();

            $this->assertSame(Memcache::getClient(), Memcache::getClient());
        }

        // APCu fallback

        public function testApcuFallbackWhenMemcachedDisabled(): void
        {
            $this->requireApcu();

            $this->assertTrue(Memcache::isAvailable());
            $this->assertTrue(Memcache::isUsingApcu());
            $this->assertNull(Memcache::getClient());
        }

        public function testApcuNotUsedWhenMemcachedEnabled(): void
        {
            $this->enableMemcache();

            $this->assertFalse(Memcache::isUsingApcu());
        }

        public function testApcuStoreFetchAddDelete(): void
        {
            $this->requireApcu();

            $this->assertTrue(Memcache::store('key', ['a' => 1]));
            $this->assertSame(['a' => 1], apcu_fetch('dw_app_key'));
            $this->assertSame(['a' => 1], Memcache::fetch('key', $success));
            $this->assertTrue($success);

            $this->assertTrue(Memcache::store('false', false));
            $this->assertFalse(Memcache::fetch('false', $success));
            $this->assertTrue($success);

            $this->assertFalse(Memcache::add('key', 'other'));
            $this->assertTrue(Memcache::add('new', 'value'));
            $this->assertTrue(Memcache::exists('new'));
            $this->assertTrue(Memcache::delete('new'));
            $this->assertFalse(Memcache::exists('new'));
            $this->assertFalse(Memcache::fetch('missing', $success));
            $this->assertFalse($success);
        }

        public function testApcuCustomKeyPrefix(): void
        {
            putenv('MEMCACHED_KEY_PREFIX=custom_');
            $this->requireApcu();

            Memcache::store('key', 'value');
            $this->assertSame('value', apcu_fetch('custom_key'));
            $this->assertFalse(apcu_exists('dw_app_key'));
        }

        public function testApcuMultipleOperations(): void
        {
            $this->requireApcu();

            $this->assertTrue(Memcache::storeMultiple(['a' => 1, 'b' => 2]));
            $this->assertSame(['a' => 1, 'b' => 2], Memcache::fetchMultiple(['a', 'b', 'c']));
            $this->assertSame([], Memcache::fetchMultiple([]));
        }

        public function testApcuIncrementAndDecrement(): void
        {
            $this->requireApcu();

            $this->assertSame(0, Memcache::increment('counter'));
            $this->assertSame(1, Memcache::increment('counter'));
            $this->assertSame(6, Memcache::increment('counter', 5));
            $this->assertSame(10, Memcache::increment('other', 1, 10));

            $this->assertSame(5, Memcache::decrement('down', 1, 5));
            $this->assertSame(3, Memcache::decrement('down', 2));
            $this->assertSame(0, Memcache::decrement('down', 10));
        }

        public function testApcuTouchAndRemember(): void
        {
            $this->requireApcu();

            $this->assertFalse(Memcache::touch('missing', 10));
            Memcache::store('key', 'value', 10);
            $this->assertTrue(Memcache::touch('key', 60));
            $this->assertSame('value', Memcache::fetch('key'));

            $calls = 0;
            $callback = function () use (&$calls) { return ++$calls; };
            $this->assertSame(1, Memcache::remember('remembered', 60, $callback));
            $this->assertSame(1, Memcache::remember('remembered', 60, $callback));
        }

        /**
         * Skips the test unless APCu is enabled in the CLI (run PHPUnit with -d apc.enable_cli=1).
         */
        private function requireApcu(): void
        {
            if (!Apcu::isExtensionAvailable())
            {
                $this->markTestSkipped('APCu is not enabled in the CLI, run with -d apc.enable_cli=1');
            }

            apcu_clear_cache();
            self::resetMemcache();
        }

        /**
         * Skips the test when APCu is enabled in the CLI, since Memcache then falls back to it.
         */
        private function requireNoApcu(): void
        {
            if (Apcu::isExtensionAvailable())
            {
                $this->markTestSkipped('APCu is enabled in the CLI, Memcache falls back to it');
            }
        }
    }
