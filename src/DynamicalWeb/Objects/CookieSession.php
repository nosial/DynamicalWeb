<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class CookieSession implements SerializableInterface
    {
        private string $sessionId;
        private array $data;
        private int $expires;
        private string $fingerprint;
        private array $cookieOptions;

        /**
         * CookieSession Constructor
         *
         * @param string $sessionId The unique session ID
         * @param array $data The session data as an associative array
         * @param int $expires The expiration timestamp of the session
         * @param string $fingerprint An optional fingerprint for additional security
         * @param array $cookieOptions Optional attributes of the session cookie (path, domain, secure, http_only, same_site)
         */
        public function __construct(string $sessionId, array $data=[], int $expires=0, string $fingerprint='', array $cookieOptions=[])
        {
            $this->sessionId = $sessionId;
            $this->data = $data;
            $this->expires = $expires;
            $this->fingerprint = $fingerprint;
            $this->cookieOptions = $cookieOptions;
        }

        /**
         * Get the unique session ID
         *
         * @return string The session ID
         */
        public function getSessionId(): string
        {
            return $this->sessionId;
        }

        /**
         * Get the expiration timestamp of the session
         *
         * @return int The expiration timestamp
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         * Get the fingerprint associated with the session
         *
         * @return string The session fingerprint
         */
        public function getFingerprint(): string
        {
            return $this->fingerprint;
        }

        /**
         * Replace the fingerprint associated with the session
         *
         * @param string $fingerprint The new session fingerprint
         */
        public function setFingerprint(string $fingerprint): void
        {
            $this->fingerprint = $fingerprint;
        }

        /**
         * Get the attributes the session cookie was created with (path, domain, secure, http_only, same_site).
         * Sessions created before these were recorded return an empty array.
         *
         * @return array The session cookie attributes
         */
        public function getCookieOptions(): array
        {
            return $this->cookieOptions;
        }

        /**
         * Get the entire session data as an associative array
         *
         * @return array The session data
         */
        public function getData(): array
        {
            return $this->data;
        }

        /**
         * Set a value in the session data
         *
         * @param string $key The key to set in the session data
         * @param mixed $value The value to associate with the key
         */
        public function set(string $key, mixed $value): void
        {
            $this->data[$key] = $value;
        }

        /**
         * Get a value from the session data
         *
         * @param string $key The key to retrieve from the session data
         * @param mixed $default The default value to return if the key does not exist
         * @return mixed The value associated with the key or the default value if the key does not exist
         */
        public function get(string $key, mixed $default = null): mixed
        {
            return $this->data[$key] ?? $default;
        }

        /**
         * Check if a key exists in the session data
         *
         * @param string $key The key to check for existence in the session data
         * @return bool True if the key exists, false otherwise
         */
        public function has(string $key): bool
        {
            return array_key_exists($key, $this->data);
        }

        /**
         * Remove a key from the session data
         *
         * @param string $key The key to remove from the session data
         */
        public function remove(string $key): void
        {
            unset($this->data[$key]);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'session_id' => $this->sessionId,
                'data' => $this->data,
                'expires' => $this->expires,
                'fingerprint' => $this->fingerprint,
                'cookie_options' => $this->cookieOptions,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): CookieSession
        {
            return new self(
                $array['session_id'],
                $array['data'] ?? [],
                $array['expires'] ?? 0,
                $array['fingerprint'] ?? '',
                $array['cookie_options'] ?? [],
            );
        }
    }
