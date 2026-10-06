<?php

    namespace DynamicalWeb\Classes;

    /**
     * In-process cache that lives for a single request.
     *
     * Use it to avoid repeating the same lookup several times while rendering one page; it is cleared
     * when a web session starts and ends. For values that should survive across requests and worker
     * processes, use {@see Memcache} (or {@see Apcu}) instead — both offer the same remember() shape.
     */
    class RequestCache
    {
        /** @var array<string, mixed> */
        private static array $entries = [];

        /**
         * Fetches an entry from the request cache.
         *
         * @param string $key Cache key
         * @param bool $success Set to true on cache hit, false on miss
         * @return mixed Cached value, or null on miss
         */
        public static function fetch(string $key, mixed &$success = false): mixed
        {
            $success = array_key_exists($key, self::$entries);
            return $success ? self::$entries[$key] : null;
        }

        /**
         * Stores a value in the request cache, overwriting any existing value.
         *
         * @param string $key Cache key
         * @param mixed $value Value to store
         */
        public static function store(string $key, mixed $value): void
        {
            self::$entries[$key] = $value;
        }

        /**
         * Checks whether a key exists in the request cache.
         *
         * @param string $key Cache key
         * @return bool
         */
        public static function exists(string $key): bool
        {
            return array_key_exists($key, self::$entries);
        }

        /**
         * Deletes an entry from the request cache.
         *
         * @param string $key Cache key
         * @return bool True if the key existed and was removed
         */
        public static function delete(string $key): bool
        {
            if (!array_key_exists($key, self::$entries))
            {
                return false;
            }

            unset(self::$entries[$key]);
            return true;
        }

        /**
         * Returns the cached value for `$key`, or computes it with `$callback`, stores it and returns it.
         * A callback that throws stores nothing, so the next call tries again.
         *
         * @param string $key Cache key
         * @param callable $callback Producer invoked on a cache miss
         * @return mixed
         */
        public static function remember(string $key, callable $callback): mixed
        {
            if (array_key_exists($key, self::$entries))
            {
                return self::$entries[$key];
            }

            $value = $callback();
            self::$entries[$key] = $value;
            return $value;
        }

        /**
         * Removes every entry from the request cache.
         */
        public static function clear(): void
        {
            self::$entries = [];
        }

        /**
         * Returns the number of entries in the request cache.
         *
         * @return int
         */
        public static function count(): int
        {
            return count(self::$entries);
        }
    }
