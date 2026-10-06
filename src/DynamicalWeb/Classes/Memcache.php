<?php

    namespace DynamicalWeb\Classes;

    use Exception;
    use Memcached;

    /**
     * Thin wrapper around the Memcached instance provided by DynamicalWeb, allowing web applications
     * to use it as a general-purpose cache without having to set up an additional caching service.
     *
     * The connection is configured through the same environment variables used by cookie sessions
     * (`MEMCACHED_ENABLED`, `MEMCACHED_HOST`, `MEMCACHED_PORT`). All keys are transparently namespaced
     * with `MEMCACHED_KEY_PREFIX` (default `dw_app_`) so application entries never collide with
     * DynamicalWeb's internal session entries.
     *
     * All methods silently no-op when Memcached is unavailable or disabled.
     */
    class Memcache
    {
        private const string PERSISTENT_ID = 'dynamicalweb_app';
        private const string DEFAULT_KEY_PREFIX = 'dw_app_';

        private static ?Memcached $memcached = null;
        private static bool $initialized = false;
        private static string $keyPrefix = self::DEFAULT_KEY_PREFIX;

        /**
         * Returns true when the Memcached extension is loaded, regardless of configuration.
         */
        public static function isExtensionAvailable(): bool
        {
            return class_exists('Memcached');
        }

        /**
         * Returns true when Memcached may be used: the extension must be available AND
         * `MEMCACHED_ENABLED` must be set to `1`, `true`, `yes` or `on`.
         */
        public static function isAvailable(): bool
        {
            return self::getClient() !== null;
        }

        /**
         * Returns the underlying Memcached client for advanced usage, or null when unavailable.
         *
         * Keys passed directly to the client are still namespaced with the configured key prefix.
         *
         * @return Memcached|null
         */
        public static function getClient(): ?Memcached
        {
            if (!self::$initialized)
            {
                self::$initialized = true;
                self::$memcached = self::createClient();
            }

            return self::$memcached;
        }

        /**
         * Returns the prefix applied to every key stored through this wrapper.
         *
         * @return string The applied prefix
         */
        public static function getKeyPrefix(): string
        {
            self::getClient();
            return self::$keyPrefix;
        }

        /**
         * Fetches an entry from Memcached.
         *
         * @param string $key Cache key
         * @param bool $success Set to true on cache hit, false on miss
         * @return mixed Cached value, or false on miss / unavailable
         */
        public static function fetch(string $key, mixed &$success = false): mixed
        {
            $client = self::getClient();
            if ($client === null)
            {
                $success = false;
                return false;
            }

            $value = $client->get($key);
            $success = $client->getResultCode() === Memcached::RES_SUCCESS;
            return $success ? $value : false;
        }

        /**
         * Stores a value in Memcached, overwriting any existing value.
         *
         * @param string $key   Cache key
         * @param mixed $value Value to store
         * @param int $ttl Time-to-live in seconds (0 = no expiry)
         * @return bool True on success, false on failure or if Memcached is unavailable
         */
        public static function store(string $key, mixed $value, int $ttl = 0): bool
        {
            return self::getClient()?->set($key, $value, $ttl) ?? false;
        }

        /**
         * Stores a value in Memcached only if the key does not already exist.
         *
         * @param string $key Cache key
         * @param mixed $value Value to store
         * @param int $ttl Time-to-live in seconds (0 = no expiry)
         * @return bool True if the value was stored, false if the key exists or Memcached is unavailable
         */
        public static function add(string $key, mixed $value, int $ttl = 0): bool
        {
            return self::getClient()?->add($key, $value, $ttl) ?? false;
        }

        /**
         * Deletes an entry from Memcached.
         *
         * @param string $key Cache key
         * @return bool True if the key existed and was removed
         */
        public static function delete(string $key): bool
        {
            return self::getClient()?->delete($key) ?? false;
        }

        /**
         * Checks whether a key exists in Memcached.
         *
         * @param string $key Cache key
         * @return bool
         */
        public static function exists(string $key): bool
        {
            $success = false;
            self::fetch($key, $success);
            return $success;
        }

        /**
         * Fetches multiple entries from Memcached.
         *
         * @param string[] $keys Cache keys
         * @return array Associative array of key => value for every key that was found
         */
        public static function fetchMultiple(array $keys): array
        {
            if (count($keys) === 0)
            {
                return [];
            }

            $result = self::getClient()?->getMulti(array_values($keys));
            return is_array($result) ? $result : [];
        }

        /**
         * Stores multiple entries in Memcached.
         *
         * @param array $items Associative array of key => value
         * @param int $ttl Time-to-live in seconds (0 = no expiry)
         * @return bool True on success, false on failure or if Memcached is unavailable
         */
        public static function storeMultiple(array $items, int $ttl = 0): bool
        {
            return self::getClient()?->setMulti($items, $ttl) ?? false;
        }

        /**
         * Increments a numeric entry, initializing it to `$initial` if it does not exist.
         *
         * @param string $key Cache key
         * @param int $offset Amount to increment by
         * @param int $initial Initial value if the key does not exist
         * @param int $ttl Time-to-live in seconds used when the key is initialized (0 = no expiry)
         * @return int|false The new value, or false on failure / unavailable
         */
        public static function increment(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
        {
            $client = self::getClient();
            if ($client === null)
            {
                return false;
            }

            // Binary protocol is required for initial values, so fall back to add() when the key is missing
            $value = $client->increment($key, $offset);
            if ($value === false && $client->getResultCode() === Memcached::RES_NOTFOUND)
            {
                if ($client->add($key, $initial, $ttl))
                {
                    return $initial;
                }

                $value = $client->increment($key, $offset);
            }

            return $value;
        }

        /**
         * Decrements a numeric entry, initializing it to `$initial` if it does not exist.
         * Memcached never decrements below zero.
         *
         * @param string $key Cache key
         * @param int $offset Amount to decrement by
         * @param int $initial Initial value if the key does not exist
         * @param int $ttl Time-to-live in seconds used when the key is initialized (0 = no expiry)
         * @return int|false The new value, or false on failure / unavailable
         */
        public static function decrement(string $key, int $offset = 1, int $initial = 0, int $ttl = 0): int|false
        {
            $client = self::getClient();
            if ($client === null)
            {
                return false;
            }

            $value = $client->decrement($key, $offset);
            if ($value === false && $client->getResultCode() === Memcached::RES_NOTFOUND)
            {
                if ($client->add($key, $initial, $ttl))
                {
                    return $initial;
                }

                $value = $client->decrement($key, $offset);
            }

            return $value;
        }

        /**
         * Updates the time-to-live of an existing entry without changing its value.
         *
         * @param string $key Cache key
         * @param int $ttl New time-to-live in seconds (0 = no expiry)
         * @return bool True on success, false if the key does not exist or Memcached is unavailable
         */
        public static function touch(string $key, int $ttl): bool
        {
            return self::getClient()?->touch($key, $ttl) ?? false;
        }

        /**
         * Returns the cached value for `$key`, or computes it with `$callback`, stores it and returns it.
         *
         * When Memcached is unavailable the callback is always invoked and its result returned uncached.
         *
         * @param string $key Cache key
         * @param int $ttl Time-to-live in seconds (0 = no expiry)
         * @param callable $callback Producer invoked on a cache miss
         * @return mixed
         */
        public static function remember(string $key, int $ttl, callable $callback): mixed
        {
            $success = false;
            $value = self::fetch($key, $success);
            if ($success)
            {
                return $value;
            }

            $value = $callback();
            self::store($key, $value, $ttl);
            return $value;
        }

        /**
         * Returns Memcached server statistics keyed by "host:port", or false when unavailable.
         *
         * @return array|false
         */
        public static function stats(): array|false
        {
            return self::getClient()?->getStats() ?? false;
        }

        /**
         * Creates the Memcached client from environment configuration.
         *
         * @return Memcached|null The client, or null if Memcached is disabled or could not be initialized
         */
        private static function createClient(): ?Memcached
        {
            $enabled = getenv('MEMCACHED_ENABLED');
            if ($enabled === false || !in_array(strtolower($enabled), ['1', 'true', 'yes', 'on'], true))
            {
                return null;
            }

            if (!self::isExtensionAvailable())
            {
                return null;
            }

            $host = getenv('MEMCACHED_HOST') ?: '127.0.0.1';
            $port = (int)(getenv('MEMCACHED_PORT') ?: 11211);
            $prefix = getenv('MEMCACHED_KEY_PREFIX');
            self::$keyPrefix = ($prefix === false || $prefix === '') ? self::DEFAULT_KEY_PREFIX : $prefix;

            try
            {
                // Persistent connections are keyed by host/port so a configuration change gets a fresh pool
                $memcached = new Memcached(self::PERSISTENT_ID . '_' . $host . '_' . $port);
                if (empty($memcached->getServerList()))
                {
                    $memcached->addServer($host, $port);
                }

                $memcached->setOption(Memcached::OPT_PREFIX_KEY, self::$keyPrefix);
                return $memcached;
            }
            catch (Exception)
            {
                return null;
            }
        }
    }
