<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Objects\CookieSession;
    use DynamicalWeb\WebSession;
    use Exception;
    use Memcached;
    use RuntimeException;

    class CookieSessionManager
    {
        private ?Memcached $memcached = null;
        private bool $enabled = false;
        private string $host;
        private int $port;
        private int $sessionTtl;
        private string $keyPrefix;
        private string $cookieName;
        private string $secret;

        /**
         * CookieSessionManager Constructor
         *
         * Initializes the Memcached client and configuration based on environment variables.
         * If Memcached is not enabled or fails to initialize, the manager will be disabled.
         */
        public function __construct()
        {
            $enabled = getenv('MEMCACHED_ENABLED');
            if ($enabled === false)
            {
                return;
            }

            $enabled = strtolower($enabled);
            if (!in_array($enabled, ['1', 'true', 'yes', 'on'], true))
            {
                return;
            }

            if (!class_exists('Memcached'))
            {
                return;
            }

            $this->host = getenv('MEMCACHED_HOST') ?: '127.0.0.1';
            $this->port = (int)(getenv('MEMCACHED_PORT') ?: 11211);
            $this->sessionTtl = (int)(getenv('MEMCACHED_SESSION_TTL') ?: 3600);
            $this->keyPrefix = getenv('MEMCACHED_SESSION_PREFIX') ?: 'dw_sess_';
            $this->cookieName = getenv('MEMCACHED_SESSION_COOKIE') ?: 'DW_SESSION';
            $this->secret = getenv('MEMCACHED_SESSION_SECRET') ?: 'dynamicalweb_default_session_secret';

            try
            {
                $this->memcached = new Memcached('dynamicalweb_session');
                $serverList = $this->memcached->getServerList();

                if (empty($serverList))
                {
                    $this->memcached->addServer($this->host, $this->port);
                }

                $this->enabled = true;
            }
            catch (Exception)
            {
                $this->memcached = null;
                $this->enabled = false;
            }
        }

        /**
         * Check if the CookieSessionManager is enabled and ready to use.
         *
         * @return bool True if enabled, false otherwise.
         */
        public function isEnabled(): bool
        {
            return $this->enabled;
        }

        /**
         * Get the name of the cookie used to store the session ID.
         *
         * @return string The cookie name.
         */
        public function getCookieName(): string
        {
            return $this->cookieName;
        }

        /**
         * Get the time-to-live (TTL) for sessions in seconds.
         *
         * @return int The session TTL in seconds.
         */
        public function getSessionTtl(): int
        {
            return $this->sessionTtl;
        }

        /**
         * Check if the current request has a session cookie.
         *
         * @return bool True if the session cookie is present, false otherwise.
         */
        public function hasSessionCookie(): bool
        {
            $request = WebSession::getRequest();
            if ($request === null)
            {
                return false;
            }

            return $request->getCookie($this->cookieName) !== null;
        }

        /**
         * Retrieve the session ID from the session cookie in the current request.
         *
         * @return string|null The session ID if present, or null if not found.
         */
        public function getSessionIdFromCookie(): ?string
        {
            $request = WebSession::getRequest();
            if ($request === null)
            {
                return null;
            }

            $value = $request->getCookie($this->cookieName);
            return $value !== null ? (string)$value : null;
        }

        /**
         * Get the current session associated with the request, if it exists and is valid.
         *
         * @return CookieSession|null The session object if a valid session exists, or null otherwise.
         */
        public function getSession(): ?CookieSession
        {
            $sessionId = $this->getSessionIdFromCookie();
            if ($sessionId === null)
            {
                return null;
            }

            return $this->fetchSession($sessionId);
        }

        /**
         * Check if a valid session exists for the current request.
         *
         * @return bool True if a valid session exists, false otherwise.
         */
        public function sessionExists(): bool
        {
            $sessionId = $this->getSessionIdFromCookie();
            if ($sessionId === null)
            {
                return false;
            }

            if (!$this->enabled || $this->memcached === null)
            {
                return false;
            }

            $key = $this->keyPrefix . $sessionId;
            $this->memcached->get($key);
            return $this->memcached->getResultCode() !== Memcached::RES_NOTFOUND;
        }

        /**
         * Create a new session with the provided data and associate it with the current request.
         *
         * @param array $data Optional initial data to store in the session.
         * @return CookieSession|null The created session object if successful, or null on failure.
         */
        public function createSession(array $data = []): ?CookieSession
        {
            if (!$this->enabled || $this->memcached === null)
            {
                return null;
            }

            $sessionId = $this->generateSessionId();
            $fingerprint = $this->computeFingerprint();
            $expires = time() + $this->sessionTtl;
            $session = new CookieSession($sessionId, $data, $expires, $fingerprint);
            if ($this->storeSession($session))
            {
                $this->setSessionCookie($sessionId, $expires);
                return $session;
            }

            return null;
        }

        /**
         * Save the provided session data to the session store and update the session cookie.
         *
         * @param CookieSession $session The session object to save.
         * @return bool True if the session was successfully saved, false otherwise.
         */
        public function saveSession(CookieSession $session): bool
        {
            return $this->storeSession($session);
        }

        /**
         * Destroy the current session associated with the request, if it exists.
         *
         * @return bool True if the session was successfully destroyed, false otherwise.
         */
        public function destroySession(): bool
        {
            $sessionId = $this->getSessionIdFromCookie();
            if ($sessionId === null)
            {
                return false;
            }

            $deleted = $this->deleteSession($sessionId);
            $this->removeSessionCookie();
            return $deleted;
        }

        /**
         * Delete a session from the session store by its session ID.
         *
         * @param string $sessionId The ID of the session to delete.
         * @return bool True if the session was successfully deleted, false otherwise.
         */
        public function deleteSession(string $sessionId): bool
        {
            if (!$this->enabled || $this->memcached === null)
            {
                return false;
            }

            return $this->memcached->delete($this->keyPrefix . $sessionId);
        }

        /**
         * Fetch a session from the session store by its session ID.
         *
         * @param string $sessionId The ID of the session to fetch.
         * @return CookieSession|null The session object if found and valid, or null otherwise.
         */
        private function fetchSession(string $sessionId): ?CookieSession
        {
            if (!$this->enabled || $this->memcached === null)
            {
                return null;
            }

            $key = $this->keyPrefix . $sessionId;
            $data = $this->memcached->get($key);

            if ($data === false || $data === null)
            {
                return null;
            }

            $session = CookieSession::fromArray($data);

            $currentFingerprint = $this->computeFingerprint();
            if ($session->getFingerprint() !== '' && $session->getFingerprint() !== $currentFingerprint)
            {
                $this->memcached->delete($key);
                $this->removeSessionCookie();
                return null;
            }

            $this->memcached->touch($key, $this->sessionTtl);
            return $session;
        }

        /**
         * Store a session in the session store.
         *
         * @param CookieSession $session The session object to store.
         * @return bool True if the session was successfully stored, false otherwise.
         */
        private function storeSession(CookieSession $session): bool
        {
            if (!$this->enabled || $this->memcached === null)
            {
                return false;
            }

            $key = $this->keyPrefix . $session->getSessionId();
            return $this->memcached->set($key, $session->toArray(), $this->sessionTtl);
        }

        /**
         * Generate a secure random session ID.
         *
         * @return string The generated session ID.
         * @throws RuntimeException If there was an error generating a secure session ID.
         */
        private function generateSessionId(): string
        {
            try
            {
                return bin2hex(random_bytes(32));
            }
            catch (Exception $e)
            {
                throw new RuntimeException('Failed to generate a secure session ID: ' . $e->getMessage(), 0, $e);
            }
        }

        /**
         * Compute a fingerprint for the current request based on the client's IP address and User-Agent string.
         *
         * @return string The computed fingerprint hash.
         */
        private function computeFingerprint(): string
        {
            $request = WebSession::getRequest();
            if ($request === null)
            {
                return '';
            }

            $ip = $request->getClientIp() ?? '';
            $ua = $request->getUserAgentString() ?? '';

            return hash_hmac('sha256', $ip . '|' . $ua, $this->secret);
        }

        /**
         * Set the session cookie in the response with the given session ID and expiration time.
         *
         * @param string $sessionId The session ID to set in the cookie.
         * @param int $expires The expiration time for the cookie (Unix timestamp).
         */
        private function setSessionCookie(string $sessionId, int $expires): void
        {
            $response = WebSession::getResponse();
            if ($response === null)
            {
                return;
            }

            $request = WebSession::getRequest();
            $secure = $request !== null && $request->isSecure();

            $response->setCookie($this->cookieName, $sessionId, $expires, '/', '', $secure, true);
        }

        /**
         * Remove the session cookie from the client's browser by setting it with an expired time.
         */
        private function removeSessionCookie(): void
        {
            $response = WebSession::getResponse();
            if ($response === null)
            {
                return;
            }

            $request = WebSession::getRequest();
            $secure = $request !== null && $request->isSecure();

            $response->setCookie($this->cookieName, '', time() - 3600, '/', '', $secure, true);
        }
    }
