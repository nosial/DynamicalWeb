<?php

    namespace DynamicalWeb\Tests\Fixtures;

    use DynamicalWeb\Classes\CookieSessionManager;
    use DynamicalWeb\Classes\Memcache;
    use DynamicalWeb\WebSession;
    use Memcached;
    use ReflectionClass;

    /**
     * In-memory Memcached replacement so tests never need a memcached server. Every instance shares one
     * store, like clients connected to the same server, and time only moves when advance() is called.
     */
    class FakeMemcached extends Memcached
    {
        /** @var array<string, array{value: mixed, expires: int}> */
        private static array $items = [];
        private static int $now = 0;

        private string $prefix = '';
        private int $resultCode = Memcached::RES_SUCCESS;

        public function __construct(?string $persistent_id = null, ?callable $callback = null, ?string $connection_str = null)
        {
            // The parent is never initialized; every method used by DynamicalWeb is overridden below
        }

        /**
         * Empties the shared store and resets the clock.
         */
        public static function reset(): void
        {
            self::$items = [];
            self::$now = 0;
        }

        /**
         * Moves the clock forward so entries with a TTL can expire without sleeping.
         */
        public static function advance(int $seconds): void
        {
            self::$now += $seconds;
        }

        /**
         * Serializes the shared store, so another process can continue from it with import().
         */
        public static function export(): string
        {
            return base64_encode(serialize(['items' => self::$items, 'now' => self::$now]));
        }

        /**
         * Replaces the shared store with one produced by export().
         */
        public static function import(string $data): void
        {
            $state = unserialize(base64_decode($data));
            self::$items = $state['items'];
            self::$now = $state['now'];
        }

        /**
         * Points the Memcache wrapper at the fake store, keeping the key prefix it read from the environment.
         * MEMCACHED_ENABLED must be set beforehand.
         */
        public static function attachToMemcache(): void
        {
            $prefix = Memcache::getKeyPrefix();
            $client = new self();
            $client->setOption(Memcached::OPT_PREFIX_KEY, $prefix);

            $reflection = new ReflectionClass(Memcache::class);
            $reflection->getProperty('memcached')->setValue(null, $client);
            $reflection->getProperty('initialized')->setValue(null, true);
        }

        /**
         * Gives WebSession a cookie session manager that uses the fake store. Does nothing when
         * MEMCACHED_ENABLED is not set, since the manager is then disabled anyway.
         */
        public static function attachToWebSession(): void
        {
            $manager = new CookieSessionManager();
            if (!$manager->isEnabled())
            {
                return;
            }

            (new ReflectionClass(CookieSessionManager::class))->getProperty('memcached')->setValue($manager, new self());
            (new ReflectionClass(WebSession::class))->getProperty('cookieSessionManager')->setValue(null, $manager);
        }

        public function get(string $key, ?callable $cache_cb = null, int $get_flags = 0): mixed
        {
            $key = $this->prefix . $key;
            if (!$this->live($key))
            {
                $this->resultCode = Memcached::RES_NOTFOUND;
                return false;
            }

            $this->resultCode = Memcached::RES_SUCCESS;
            return self::$items[$key]['value'];
        }

        public function set(string $key, mixed $value, int $expiration = 0): bool
        {
            self::$items[$this->prefix . $key] = ['value' => $value, 'expires' => $this->expiresAt($expiration)];
            $this->resultCode = Memcached::RES_SUCCESS;
            return true;
        }

        public function add(string $key, mixed $value, int $expiration = 0): bool
        {
            if ($this->live($this->prefix . $key))
            {
                $this->resultCode = Memcached::RES_NOTSTORED;
                return false;
            }

            return $this->set($key, $value, $expiration);
        }

        public function delete(string $key, int $time = 0): bool
        {
            $key = $this->prefix . $key;
            if (!$this->live($key))
            {
                $this->resultCode = Memcached::RES_NOTFOUND;
                return false;
            }

            unset(self::$items[$key]);
            $this->resultCode = Memcached::RES_SUCCESS;
            return true;
        }

        public function touch(string $key, int $expiration = 0): bool
        {
            $key = $this->prefix . $key;
            if (!$this->live($key))
            {
                $this->resultCode = Memcached::RES_NOTFOUND;
                return false;
            }

            self::$items[$key]['expires'] = $this->expiresAt($expiration);
            $this->resultCode = Memcached::RES_SUCCESS;
            return true;
        }

        public function getResultCode(): int
        {
            return $this->resultCode;
        }

        public function getMulti(array $keys, int $get_flags = 0): array|false
        {
            $result = [];
            foreach ($keys as $key)
            {
                if ($this->live($this->prefix . $key))
                {
                    $result[$key] = self::$items[$this->prefix . $key]['value'];
                }
            }

            $this->resultCode = Memcached::RES_SUCCESS;
            return $result;
        }

        public function setMulti(array $items, int $expiration = 0): bool
        {
            foreach ($items as $key => $value)
            {
                $this->set((string)$key, $value, $expiration);
            }

            return true;
        }

        public function increment(string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): int|false
        {
            return $this->adjust($key, $offset);
        }

        public function decrement(string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): int|false
        {
            return $this->adjust($key, -$offset);
        }

        public function getStats(?string $type = null): array|false
        {
            return ['fake:11211' => ['curr_items' => count(self::$items)]];
        }

        public function flush(int $delay = 0): bool
        {
            self::$items = [];
            return true;
        }

        public function setOption(int $option, mixed $value): bool
        {
            if ($option === Memcached::OPT_PREFIX_KEY)
            {
                $this->prefix = (string)$value;
            }

            return true;
        }

        public function getOption(int $option): mixed
        {
            return $option === Memcached::OPT_PREFIX_KEY ? $this->prefix : false;
        }

        public function getServerList(): array
        {
            return [['host' => 'fake', 'port' => 11211, 'type' => 'TCP']];
        }

        public function addServer(string $host, int $port, int $weight = 0): bool
        {
            return true;
        }

        /**
         * Like memcached, a missing key fails without the binary protocol and the value never drops below zero.
         */
        private function adjust(string $key, int $delta): int|false
        {
            $fullKey = $this->prefix . $key;
            if (!$this->live($fullKey))
            {
                $this->resultCode = Memcached::RES_NOTFOUND;
                return false;
            }

            $value = max(0, (int)self::$items[$fullKey]['value'] + $delta);
            self::$items[$fullKey]['value'] = $value;
            $this->resultCode = Memcached::RES_SUCCESS;
            return $value;
        }

        private function live(string $fullKey): bool
        {
            if (!array_key_exists($fullKey, self::$items))
            {
                return false;
            }

            $expires = self::$items[$fullKey]['expires'];
            if ($expires !== 0 && $expires <= self::$now)
            {
                unset(self::$items[$fullKey]);
                return false;
            }

            return true;
        }

        private function expiresAt(int $ttl): int
        {
            return $ttl === 0 ? 0 : self::$now + $ttl;
        }
    }
