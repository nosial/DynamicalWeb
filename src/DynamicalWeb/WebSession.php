<?php

    namespace DynamicalWeb;

    use DynamicalWeb\Classes\Apcu;
    use DynamicalWeb\Classes\CookieSessionManager;
    use DynamicalWeb\Classes\Logger;
    use DynamicalWeb\Classes\RequestCache;
    use DynamicalWeb\Classes\Router;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Exceptions\LocaleException;
    use DynamicalWeb\Exceptions\WebSocketException;
    use DynamicalWeb\Html\Functions;
    use DynamicalWeb\Objects\CookieSession;
    use DynamicalWeb\Objects\Locale;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\Objects\WebSocket;
    use Exception;
    use Symfony\Component\Yaml\Exception\ParseException;
    use Symfony\Component\Yaml\Yaml;
    use Throwable;

    class WebSession
    {
        public const string CSRF_FIELD_NAME = 'csrf_token';
        public const string CSRF_HEADER_NAME = 'X-CSRF-Token';
        private const string CSRF_SESSION_KEY = '_dw_csrf_token';
        private const string FLASH_SESSION_KEY = '_dw_flash';

        private static ?DynamicalWeb $instance=null;
        private static ?Request $request=null;
        private static ?Response $response=null;
        private static ?string $module=null;
        private static ?Route $currentRoute=null;
        private static ?Locale $locale=null;
        private static ?Throwable $exception=null;
        private static ?WebSocket $websocket=null;
        private static array $localeFileCache=[];
        private static ?array $variables;
        private static ?CookieSessionManager $cookieSessionManager=null;
        /** @var array<string, CookieSession> Cookie sessions loaded during this request, keyed by cookie name */
        private static array $loadedCookieSessions=[];
        /** @var array<string, CookieSession> Cookie sessions created during this request, keyed by cookie name */
        private static array $createdCookieSessions=[];

        /**
         * Starts the web session instance with the provided DynamicalWeb instance.
         * The best-matching locale is loaded once here and reused for the entire request.
         *
         * @param DynamicalWeb $dynamicalWeb The DynamicalWeb instance to initialize the web session with.
         * @throws LocaleException If locale is configured but cannot be loaded
         */
        public static function startSession(DynamicalWeb $dynamicalWeb): void
        {
            self::$instance = $dynamicalWeb;
            self::$request = new Request($dynamicalWeb->getWebConfiguration(), $dynamicalWeb->getPackage(), $dynamicalWeb);
            self::$response = new Response();

            if (getenv('WSS_ENABLED') === '1')
            {
                try
                {
                    self::$websocket = new WebSocket();
                    Logger::getLogger()->debug('WebSocket connection established via TCP bridge');
                }
                catch (WebSocketException $e)
                {
                    Logger::getLogger()->warning('Failed to initialize WebSocket connection: ' . $e->getMessage());
                    self::$websocket = null;
                }
            }

            $routeResult = Router::findRouteWithDetails(
                webConfiguration: self::$instance->getWebConfiguration(),
                request: self::$request,
                webRootPath: self::$instance->getWebRootPath(),
                webResourcesPath: self::$instance->getWebResourcesPath()
            );
            self::$module = $routeResult->getModule();
            self::$currentRoute = $routeResult->getRoute();
            self::$exception = null;
            self::$variables = [];
            self::$loadedCookieSessions = [];
            self::$createdCookieSessions = [];
            RequestCache::clear();
            self::loadLocale();
        }

        /**
         * Ensures that a Response object exists in the session. If startSession() failed before
         * creating one, this method creates a fresh Response so error handling can proceed.
         */
        public static function ensureResponse(): void
        {
            if (self::$response === null)
            {
                self::$response = new Response();
            }
        }

        /**
         * Ends the session by sending the response (if available), closing the WebSocket connection,
         * and clearing all static state. If an exit code is provided, the process will exit after cleanup.
         *
         * @param int|null $exitCode Optional exit code. When null, the process continues (no exit).
         *                           When set to an integer, the process exits with that code after cleanup.
         */
        public static function endSession(?int $exitCode = null): void
        {
            // Send response if available
            if (self::$response !== null)
            {
                try
                {
                    self::$response->send();
                }
                catch (Throwable)
                {
                    // Response sending failed, proceed with cleanup
                }

                self::$response = null;
            }

            if (self::$websocket !== null)
            {
                if (self::$websocket->isConnected())
                {
                    Logger::getLogger()->debug('WebSession: closing WebSocket connection on session end');
                }
                self::$websocket->close();
                self::$websocket = null;
            }

            self::$instance = null;
            self::$request = null;
            self::$module = null;
            self::$currentRoute = null;
            self::$locale = null;
            self::$exception = null;
            self::$variables = null;
            self::$cookieSessionManager = null;
            self::$loadedCookieSessions = [];
            self::$createdCookieSessions = [];
            RequestCache::clear();

            if ($exitCode !== null)
            {
                exit($exitCode);
            }
        }

        /**
         * Returns the DynamicalWeb instance associated with the current web session.
         *
         * @return DynamicalWeb|null
         */
        public static function getInstance(): ?DynamicalWeb
        {
            return self::$instance;
        }

        /**
         * Returns the Request object associated with the current web session.
         *
         * @return Request|null
         */
        public static function getRequest(): ?Request
        {
            return self::$request;
        }

        /**
         * Returns the configurable Response object associated with the current web session.
         *
         * @return Response|null
         */
        public static function getResponse(): ?Response
        {
            return self::$response;
        }

        /**
         * Sets the Response object for the current web session.
         *
         * @param Response|null $response The Response object to set, or null to clear.
         */
        public static function setResponse(?Response $response): void
        {
            self::$response = $response;
        }

        /**
         * Returns the name of the module being accessed in the current web session.
         *
         * @return string|null
         */
        public static function getModule(): ?string
        {
            return self::$module;
        }

        /**
         * Returns the exception that occurred during the current web session, if any.
         *
         * @return Throwable|null
         */
        public static function getException(): ?Throwable
        {
            return self::$exception;
        }

        /**
         * Sets the exception that occurred during the current web session.
         *
         * @param Throwable|null $exception
         */
        public static function setException(?Throwable $exception): void
        {
            self::$exception = $exception;
        }

        /**
         * Returns the current route being accessed in the web session.
         *
         * @return Route|null
         */
        public static function getCurrentRoute(): ?Route
        {
            return self::$currentRoute;
        }

        /**
         * Returns the loaded Locale object for the current web session.
         *
         * @return Locale|null
         */
        public static function getLocale(): ?Locale
        {
            return self::$locale;
        }

        /**
         * Returns the WebSocket object associated with the current web session.
         *
         * @return WebSocket|null
         */
        public static function getWebSocket(): ?WebSocket
        {
            return self::$websocket;
        }

        /**
         * Sets the WebSocket object associated with the current web session.
         *
         * @param WebSocket|null $websocket The WebSocket object to set.
         */
        public static function setWebSocket(?WebSocket $websocket): void
        {
            self::$websocket = $websocket;
        }

        /**
         * Checks if a WebSocket object is associated with the current web session.
         *
         * @return bool True if a WebSocket object is set, false otherwise.
         */
        public static function hasWebSocket(): bool
        {
            return self::$websocket !== null;
        }

        /**
         * Sets a custom variable in the web session that can be accessed globally during the request lifecycle.
         *
         * @param string $key The key to identify the variable.
         * @param mixed $value The value to store, which can be of any type.
         */
        public static function set(string $key, mixed $value): void
        {
            if (self::$variables === null)
            {
                self::$variables = [];
            }

            self::$variables[$key] = $value;
        }

        /**
         * Retrieves a custom variable from the web session by its key.
         *
         * @param string $key The key of the variable to retrieve.
         * @return mixed The value of the variable, or null if it does not exist.
         */
        public static function get(string $key): mixed
        {
            if (self::$variables === null || !array_key_exists($key, self::$variables))
            {
                return null;
            }

            return self::$variables[$key];
        }

        /**
         * Checks if a custom variable exists in the web session.
         *
         * @param string $key The key of the variable to check.
         * @return bool True if the variable exists, false otherwise.
         */
        public static function exists(string $key): bool
        {
            return self::$variables !== null && array_key_exists($key, self::$variables);
        }

        /**
         * Unsets a custom variable from the web session.
         *
         * @param string $key The key of the variable to unset.
         */
        public static function unset(string $key): void
        {
            if (self::$variables !== null)
            {
                unset(self::$variables[$key]);
            }
        }

        /**
         * Returns the CookieSessionManager instance if cookie sessions are enabled, or null if not.
         * The manager is lazily initialized on first access and cached for subsequent calls.
         *
         * @return CookieSessionManager|null
         */
        public static function getCookieSessionManager(): ?CookieSessionManager
        {
            if (self::$cookieSessionManager === null)
            {
                self::$cookieSessionManager = new CookieSessionManager();
            }

            return self::$cookieSessionManager->isEnabled() ? self::$cookieSessionManager : null;
        }

        /**
         * Checks if a valid cookie session exists for the current request.
         *
         * @param string|null $cookieName Optional cookie name to check. Defaults to the configured cookie name.
         * @return bool True if a valid cookie session exists, false otherwise.
         */
        public static function hasCookieSession(?string $cookieName = null): bool
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return false;
            }

            return $manager->hasSessionCookie($cookieName) && $manager->getSession($cookieName) !== null;
        }

        /**
         * Retrieves the current cookie session for the request, or null if no valid session exists.
         *
         * @param string|null $cookieName Optional cookie name to read. Defaults to the configured cookie name.
         * @return CookieSession|null The current cookie session, or null if not found or invalid.
         */
        public static function getCookieSession(?string $cookieName=null): ?CookieSession
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return null;
            }

            // Every caller in a request shares one instance, so changes saved by one are never
            // overwritten by another caller saving an older copy of the same session
            $name = $cookieName ?? $manager->getCookieName();
            if (isset(self::$loadedCookieSessions[$name]))
            {
                return self::$loadedCookieSessions[$name];
            }

            $session = $manager->getSession($cookieName);
            if ($session !== null)
            {
                self::$loadedCookieSessions[$name] = $session;
            }

            return $session;
        }

        /**
         * Creates a new cookie session with the provided data and returns it.
         *
         * @param array $data Optional associative array of data to store in the session.
         * @param string|null $cookieName Optional cookie name to set. Defaults to the configured cookie name.
         * @param string $path The cookie path. Defaults to '/'.
         * @param string $domain The cookie domain. Defaults to '' (current domain).
         * @param bool|null $secure Whether the cookie should only be sent over HTTPS. Null = auto-detect from request.
         * @param bool $httpOnly Whether the cookie should be accessible only via HTTP. Defaults to true.
         * @param string $sameSite The SameSite attribute (None, Lax, or Strict). Defaults to 'Lax'.
         * @return CookieSession|null The newly created cookie session, or null if cookie sessions are not enabled.
         */
        public static function createCookieSession(array $data = [], ?string $cookieName = null, string $path = '/', string $domain = '', ?bool $secure = null, bool $httpOnly = true, string $sameSite = 'Lax'): ?CookieSession
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return null;
            }

            $session = $manager->createSession($data, $cookieName, $path, $domain, $secure, $httpOnly, $sameSite);
            if ($session !== null)
            {
                self::$createdCookieSessions[$cookieName ?? $manager->getCookieName()] = $session;
            }

            return $session;
        }

        /**
         * Saves the provided cookie session data and updates the session cookie in the response.
         *
         * @param CookieSession $session The cookie session to save.
         * @return bool True if the session was successfully saved, false otherwise.
         */
        public static function saveCookieSession(CookieSession $session): bool
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return false;
            }

            return $manager->saveSession($session);
        }

        /**
         * Destroys the current cookie session and removes the session cookie from the response.
         *
         * @param string|null $cookieName Optional cookie name to read and expire. Defaults to the configured cookie name.
         * @param string $path The cookie path used when the session was created. Defaults to '/'.
         * @param string $domain The cookie domain used when the session was created. Defaults to ''.
         * @return bool True if the session was successfully destroyed, false otherwise.
         */
        public static function destroyCookieSession(?string $cookieName = null, string $path = '/', string $domain = ''): bool
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return false;
            }

            $name = $cookieName ?? $manager->getCookieName();
            unset(self::$loadedCookieSessions[$name], self::$createdCookieSessions[$name]);
            return $manager->destroySession($cookieName, $path, $domain);
        }

        /**
         * Returns the cookie session for the current request: the one loaded from the request cookie, or the one
         * created earlier in this request. When neither exists and $create is true, a new session is created.
         *
         * @param string|null $cookieName Optional cookie name. Defaults to the configured cookie name.
         * @param bool $create Whether to create a session when none exists.
         * @return CookieSession|null The session, or null if cookie sessions are disabled or none exists.
         */
        private static function resolveCookieSession(?string $cookieName, bool $create): ?CookieSession
        {
            $manager = self::getCookieSessionManager();
            if ($manager === null)
            {
                return null;
            }

            $session = self::getCookieSession($cookieName) ?? self::$createdCookieSessions[$cookieName ?? $manager->getCookieName()] ?? null;
            if ($session === null && $create)
            {
                $session = self::createCookieSession([], $cookieName);
            }

            return $session;
        }

        /**
         * Returns the CSRF token of the current cookie session, creating the session and token on first use.
         *
         * Forms echo it back in the {@see WebSession::CSRF_FIELD_NAME} field (see {@see Functions::csrfField()})
         * and scripts in the {@see WebSession::CSRF_HEADER_NAME} header (see {@see Functions::csrfMeta()}).
         *
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         * @return string|null The token, or null if cookie sessions are disabled.
         */
        public static function getCsrfToken(?string $cookieName = null): ?string
        {
            $session = self::resolveCookieSession($cookieName, true);
            if ($session === null)
            {
                return null;
            }

            $token = $session->get(self::CSRF_SESSION_KEY);
            if (!is_string($token) || $token === '')
            {
                $token = bin2hex(random_bytes(32));
                $session->set(self::CSRF_SESSION_KEY, $token);
                self::saveCookieSession($session);
            }

            return $token;
        }

        /**
         * Checks a submitted CSRF token against the current cookie session's token.
         *
         * @param string|null $token The submitted token. When null, it is read from the
         *                           {@see WebSession::CSRF_FIELD_NAME} parameter or the
         *                           {@see WebSession::CSRF_HEADER_NAME} header of the current request.
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         * @return bool True if the session has a token and the submitted token matches it.
         */
        public static function verifyCsrfToken(?string $token = null, ?string $cookieName = null): bool
        {
            if ($token === null && self::$request !== null)
            {
                $parameter = self::$request->getParameters()[self::CSRF_FIELD_NAME] ?? null;
                $token = is_string($parameter) ? $parameter : self::$request->getHeader(self::CSRF_HEADER_NAME);
            }

            $expected = self::resolveCookieSession($cookieName, false)?->get(self::CSRF_SESSION_KEY);
            return is_string($expected) && $expected !== '' && is_string($token) && hash_equals($expected, $token);
        }

        /**
         * Stores a value in the cookie session until it is read with {@see WebSession::getFlash()}, typically to show
         * a message on the page a redirect leads to. Creates the cookie session if it does not exist.
         *
         * @param string $key The flash key.
         * @param mixed $value The value, which must be serializable.
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         * @return bool True if the value was stored, false if cookie sessions are disabled or saving failed.
         */
        public static function flash(string $key, mixed $value, ?string $cookieName = null): bool
        {
            $session = self::resolveCookieSession($cookieName, true);
            if ($session === null)
            {
                return false;
            }

            $flash = $session->get(self::FLASH_SESSION_KEY, []);
            $flash = is_array($flash) ? $flash : [];
            $flash[$key] = $value;
            $session->set(self::FLASH_SESSION_KEY, $flash);
            return self::saveCookieSession($session);
        }

        /**
         * Returns a value stored with {@see WebSession::flash()} and removes it, so it is only shown once.
         *
         * @param string $key The flash key.
         * @param mixed $default The value to return when the key is not set.
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         * @return mixed The flashed value, or $default.
         */
        public static function getFlash(string $key, mixed $default = null, ?string $cookieName = null): mixed
        {
            $session = self::resolveCookieSession($cookieName, false);
            $flash = $session?->get(self::FLASH_SESSION_KEY, []);
            if (!is_array($flash) || !array_key_exists($key, $flash))
            {
                return $default;
            }

            $value = $flash[$key];
            unset($flash[$key]);
            if (count($flash) === 0)
            {
                $session->remove(self::FLASH_SESSION_KEY);
            }
            else
            {
                $session->set(self::FLASH_SESSION_KEY, $flash);
            }

            self::saveCookieSession($session);
            return $value;
        }

        /**
         * Checks if a value stored with {@see WebSession::flash()} is waiting to be read, without removing it.
         *
         * @param string $key The flash key.
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         * @return bool True if the flash key is set.
         */
        public static function hasFlash(string $key, ?string $cookieName = null): bool
        {
            $flash = self::resolveCookieSession($cookieName, false)?->get(self::FLASH_SESSION_KEY, []);
            return is_array($flash) && array_key_exists($key, $flash);
        }

        /**
         * Redirects to the given URL, sends the response and ends the request.
         *
         * @param string $url The URL to redirect to.
         * @param ResponseCode|null $statusCode Optional redirect status code. Defaults to 302 Found.
         */
        public static function redirectTo(string $url, ?ResponseCode $statusCode = null): void
        {
            self::ensureResponse();
            self::$response->setRedirect($url, $statusCode);
            self::endSession(0);
        }

        /**
         * Redirects to a named route, sends the response and ends the request.
         *
         * @param string $id The route ID as defined in the web configuration.
         * @param array $pathVariables Associative array of path variable substitutions.
         * @param array $queryParams Associative array of query string parameters to append.
         * @param ResponseCode|null $statusCode Optional redirect status code. Defaults to 302 Found.
         */
        public static function redirectToRoute(string $id, array $pathVariables = [], array $queryParams = [], ?ResponseCode $statusCode = null): void
        {
            self::redirectTo(Functions::getRouteUrl($id, $pathVariables, $queryParams), $statusCode);
        }

        /**
         * Sends a JSON response and ends the request.
         *
         * @param mixed $data The data to encode as JSON.
         * @param ResponseCode|int $statusCode The response status code. Defaults to 200 OK.
         */
        public static function respondJson(mixed $data, ResponseCode|int $statusCode = ResponseCode::OK): void
        {
            self::ensureResponse();
            self::$response->setStatusCode($statusCode);
            self::$response->setJson($data);
            self::endSession(0);
        }

        /**
         * Sends an error response and ends the request. Without a message, the router's response handler for the
         * status code is rendered when one is configured; otherwise a plain text response is sent.
         *
         * @param ResponseCode|int $statusCode The response status code.
         * @param string|null $message Optional plain text body.
         */
        public static function abort(ResponseCode|int $statusCode, ?string $message = null): void
        {
            self::ensureResponse();
            $code = $statusCode instanceof ResponseCode ? $statusCode : (ResponseCode::tryFrom($statusCode) ?? ResponseCode::INTERNAL_SERVER_ERROR);
            if ($message !== null || self::$instance === null || !self::$instance->renderResponseHandler($code))
            {
                self::$response->setStatusCode($code);
                self::$response->setContentType('text/plain');
                self::$response->setBody($message ?? $code->value . ' ' . $code->getMessage());
            }

            self::endSession(0);
        }

        /**
         * Loads the single best-matching locale for the current request.
         * Priority: locale cookie → Accept-Language → configured default → first available.
         * Skipped silently if no locales are configured.
         *
         * @throws LocaleException If locale is configured but the file cannot be loaded or parsed
         */
        private static function loadLocale(): void
        {
            $availableLocales = self::$instance->getAvailableLocaleCodes();
            if (empty($availableLocales))
            {
                return;
            }

            $defaultLocale = self::$instance->getWebConfiguration()->getApplication()->getDefaultLocale();

            // Priority 1: enforced locale cookie (set by /dynaweb/language/{id})
            $cookieLocale = self::$request->getCookie('locale');
            if (is_string($cookieLocale))
            {
                $cookieLocale = preg_replace('/[^a-z0-9_\-]/i', '', $cookieLocale);
                $cookieLocale = strtolower(substr($cookieLocale, 0, 10));
            }

            if ($cookieLocale !== null && $cookieLocale !== '' && in_array($cookieLocale, $availableLocales, true))
            {
                $localeToLoad = $cookieLocale;
            }
            // Priority 2: Accept-Language header detection
            elseif (($detectedLanguage = self::$request->getDetectedLanguage()) !== null && in_array($detectedLanguage, $availableLocales, true))
            {
                $localeToLoad = $detectedLanguage;
            }
            // Priority 3: configured default locale
            elseif ($defaultLocale !== null && in_array($defaultLocale, $availableLocales, true))
            {
                $localeToLoad = $defaultLocale;
            }
            // Priority 4: first available locale as last resort
            else
            {
                $localeToLoad = $availableLocales[0];
            }

            $localeFilePath = self::$instance->getLocaleFilePath($localeToLoad);
            if ($localeFilePath === null || !file_exists($localeFilePath))
            {
                throw new LocaleException(sprintf('Locale file not found for locale "%s" at path "%s"', $localeToLoad, $localeFilePath ?? 'unknown'));
            }

            try
            {
                $localeData = self::loadLocaleData($localeFilePath);
                if (!is_array($localeData))
                {
                    throw new LocaleException(sprintf('Invalid locale file format for locale "%s" at path "%s"', $localeToLoad, $localeFilePath));
                }

                self::$locale = new Locale($localeToLoad, $localeData);
            }
            catch (Exception $e)
            {
                throw new LocaleException(sprintf('Failed to parse locale file for locale "%s": %s', $localeToLoad, $e->getMessage()), 0, $e);
            }
        }

        /**
         * Loads and caches locale YAML data by file path.
         * Uses APCu shared-memory cache when available, with an in-process static cache as fallback.
         *
         * @param string $localeFilePath Absolute path to the locale YAML file
         * @return mixed Parsed locale data
         * @throws ParseException If the file cannot be parsed
         */
        private static function loadLocaleData(string $localeFilePath): mixed
        {
            // In-process cache — avoids re-parsing within the same worker process
            if (isset(self::$localeFileCache[$localeFilePath]))
            {
                return self::$localeFileCache[$localeFilePath];
            }

            // APCu shared-memory cache — shared across worker processes
            $cacheKey = 'dw_locale_' . md5($localeFilePath);
            $data = Apcu::fetch($cacheKey, $success);
            if ($success && is_array($data))
            {
                self::$localeFileCache[$localeFilePath] = $data;
                return $data;
            }

            $data = Yaml::parseFile($localeFilePath);
            Apcu::store($cacheKey, $data, 300);
            self::$localeFileCache[$localeFilePath] = $data;
            return $data;
        }
    }
