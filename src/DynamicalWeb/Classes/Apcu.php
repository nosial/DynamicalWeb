<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\WebSession;

    /**
     * Thin wrapper around APCu functions.
     *
     * All methods silently no-op when APCu is unavailable or when the
     * application has opted out via the `disable_apcu` configuration flag.
     */
    class Apcu
    {
        /**
         * Returns true when the APCu extension is loaded and active in the current SAPI,
         * regardless of application configuration.
         */
        public static function isExtensionAvailable(): bool
        {
            return function_exists('apcu_enabled') && apcu_enabled();
        }

        /**
         * Returns true when APCu may be used: the extension must be available
         * AND the application must not have disabled APCu via `disable_apcu`.
         */
        public static function isAvailable(): bool
        {
            if (!self::isExtensionAvailable())
            {
                return false;
            }

            $instance = WebSession::getInstance();
            if ($instance !== null && $instance->getWebConfiguration()->getApplication()->isApcuDisabled())
            {
                return false;
            }

            return true;
        }

        /**
         * Fetches an entry from APCu.
         *
         * @param string $key     Cache key
         * @param bool   $success Set to true on cache hit, false on miss
         * @return mixed          Cached value, or false on miss / unavailable
         */
        public static function fetch(string $key, mixed &$success = false): mixed
        {
            if (!self::isAvailable())
            {
                $success = false;
                return false;
            }

            return apcu_fetch($key, $success);
        }

        /**
         * Stores a value in APCu.
         *
         * @param string $key   Cache key
         * @param mixed  $value Value to store
         * @param int    $ttl   Time-to-live in seconds (0 = no expiry)
         * @return bool         True on success, false if APCu is unavailable
         */
        public static function store(string $key, mixed $value, int $ttl = 0): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_store($key, $value, $ttl);
        }

        /**
         * Deletes an entry from APCu.
         *
         * @param string $key Cache key
         * @return bool       True if the key existed and was removed
         */
        public static function delete(string $key): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_delete($key);
        }

        /**
         * Checks whether a key exists in the APCu cache.
         *
         * @param string $key Cache key
         * @return bool
         */
        public static function exists(string $key): bool
        {
            if (!self::isAvailable())
            {
                return false;
            }

            return apcu_exists($key);
        }

        /**
         * Returns APCu cache info, or false when unavailable.
         *
         * @param bool $limited When true only summary info is returned (no per-entry data)
         * @return array|false
         */
        public static function cacheInfo(bool $limited = false): array|false
        {
            if (!self::isExtensionAvailable())
            {
                return false;
            }

            return apcu_cache_info($limited);
        }

        /**
         * Returns APCu shared-memory info, or false when unavailable.
         *
         * @param bool $limited When true only summary info is returned
         * @return array|false
         */
        public static function smaInfo(bool $limited = false): array|false
        {
            if (!self::isExtensionAvailable())
            {
                return false;
            }

            return apcu_sma_info($limited);
        }
    }
