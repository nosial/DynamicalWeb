<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class Cookie implements SerializableInterface
    {
        private string $name;
        private string $value;
        private int $expires;
        private string $path;
        private string $domain;
        private bool $secure;
        private bool $httpOnly;

        /**
         * Cookie Constructor
         *
         * @param string $name The name of the cookie
         * @param string $value The value of the cooke (Visible to the client-side)
         * @param int $expires The time the cookie is set to expire
         * @param string $path The path the cookie is only valid in
         * @param string $domain The domain the cookie is only valid in
         * @param bool $secure True if the Cookie can only be used in secured connections
         * @param bool $httpOnly True if the Cookie can only be used in HTTP only connections
         */
        public function __construct(string $name, string $value, int $expires=0, string $path='/', string $domain='', bool $secure=false, bool $httpOnly=false)
        {
            $this->name = $name;
            $this->value = $value;
            $this->expires = $expires;
            $this->path = $path;
            $this->domain = $domain;
            $this->secure = $secure;
            $this->httpOnly = $httpOnly;
        }

        /**
         * Returns the name of the Cookie
         *
         * @return string THe name of the Cookie
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Sets the name of the Cookie
         *
         * @param string $name The name of the cookie to set
         */
        public function setName(string $name): void
        {
            $this->name = $name;
        }

        /**
         * Returns the value pof the Cookie
         *
         * @return string The Cookie value
         */
        public function getValue(): string
        {
            return $this->value;
        }

        /**
         * Sets the value of the Cookie, this value is visible to the client side
         *
         * @param string $value The value of the cookie to set
         */
        public function setValue(string $value): void
        {
            $this->value = $value;
        }

        /**
         * Returns the seconds for when the Cookie expires
         *
         * @return int The seconds when the Cookie epxires
         */
        public function getExpires(): int
        {
            return $this->expires;
        }

        /**
         * Sets the seconds for when the Cookie expires
         *
         * @param int $expires The seconds when the Cookie expires
         */
        public function setExpires(int $expires): void
        {
            $this->expires = $expires;
        }

        /**
         * Returns the path the Cookie is only valid in
         *
         * @return string The path the Cookie is only valid in
         */
        public function getPath(): string
        {
            return $this->path;
        }

        /**
         * Sets the path the Cookie is only valid in
         *
         * @param string $path The path the Cookie is only valid in
         */
        public function setPath(string $path): void
        {
            $this->path = $path;
        }

        /**
         * Returns the domain the Cookie is only valid in
         *
         * @return string The domain the Cookie is only valid in
         */
        public function getDomain(): string
        {
            return $this->domain;
        }

        /**
         * Sets the domain the Cookie is only valid in
         *
         * @param string $domain The domain the Cookie is only valid in
         */
        public function setDomain(string $domain): void
        {
            $this->domain = $domain;
        }

        /**
         * Returns true if the Cookie can only be used in secured connections
         *
         * @return bool True if the Cookie can only be used in secured connections
         */
        public function isSecure(): bool
        {
            return $this->secure;
        }

        /**
         * Sets whether the Cookie can only be used in secured connections
         *
         * @param bool $secure True if the Cookie can only be used in secured connections
         */
        public function setSecure(bool $secure): void
        {
            $this->secure = $secure;
        }

        /**
         * Returns true if the Cookie can only be used in HTTP only connections
         *
         * @return bool True if the Cookie can only be used in HTTP only connections
         */
        public function isHttpOnly(): bool
        {
            return $this->httpOnly;
        }

        /**
         * Sets whether the Cookie can only be used in HTTP only connections
         *
         * @param bool $httpOnly True if the Cookie can only be used in HTTP only connections
         */
        public function setHttpOnly(bool $httpOnly): void
        {
            $this->httpOnly = $httpOnly;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'value' => $this->value,
                'expires' => $this->expires,
                'path' => $this->path,
                'domain' => $this->domain,
                'secure' => $this->secure,
                'httpOnly' => $this->httpOnly
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): SerializableInterface
        {
            return new self(
                $array['name'],
                $array['value'],
                $array['expires'] ?? 0,
                $array['path'] ?? '/',
                $array['domain'] ?? '',
                $array['secure'] ?? false,
                $array['httpOnly'] ?? false
            );
        }
    }
