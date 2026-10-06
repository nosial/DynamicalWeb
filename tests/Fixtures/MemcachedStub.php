<?php

    /**
     * Stand-in for the Memcached extension class, loaded only when the extension is missing so
     * FakeMemcached has something to extend. Every method behaves like a client with no servers.
     */
    class Memcached
    {
        public const int RES_SUCCESS = 0;
        public const int RES_NOTSTORED = 14;
        public const int RES_NOTFOUND = 16;
        public const int OPT_PREFIX_KEY = -1002;

        public function __construct(?string $persistent_id = null, ?callable $callback = null, ?string $connection_str = null) {}
        public function get(string $key, ?callable $cache_cb = null, int $get_flags = 0): mixed { return false; }
        public function set(string $key, mixed $value, int $expiration = 0): bool { return false; }
        public function add(string $key, mixed $value, int $expiration = 0): bool { return false; }
        public function delete(string $key, int $time = 0): bool { return false; }
        public function touch(string $key, int $expiration = 0): bool { return false; }
        public function getResultCode(): int { return self::RES_NOTFOUND; }
        public function getMulti(array $keys, int $get_flags = 0): array|false { return false; }
        public function setMulti(array $items, int $expiration = 0): bool { return false; }
        public function increment(string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): int|false { return false; }
        public function decrement(string $key, int $offset = 1, int $initial_value = 0, int $expiry = 0): int|false { return false; }
        public function getStats(?string $type = null): array|false { return false; }
        public function flush(int $delay = 0): bool { return false; }
        public function setOption(int $option, mixed $value): bool { return false; }
        public function getOption(int $option): mixed { return false; }
        public function getServerList(): array { return []; }
        public function addServer(string $host, int $port, int $weight = 0): bool { return false; }
    }
