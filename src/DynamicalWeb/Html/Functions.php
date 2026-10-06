<?php

    namespace DynamicalWeb\Html;

    use DynamicalWeb\Classes\DebugPanel;
    use DynamicalWeb\Classes\ExecutionHandler;
    use DynamicalWeb\Exceptions\ExecutionException;
    use DynamicalWeb\Interfaces\StringInterface;
    use DynamicalWeb\WebSession;
    use RuntimeException;

    class Functions
    {
        private static ?string $activeLocaleSection=null;

        /**
         * Prints out the given input, optionally escapes the input so
         *
         * @param mixed $text The text to print
         * @param bool $escape if True, escapes the HTML encoding from the text
         */
        public static function print(mixed $text, bool $escape=true): void
        {
            // Support for classes/types that cannot be cast to string directly but implement the StringInterface
            if($text instanceof StringInterface)
            {
                $text = $text->toString();
            }

            if($escape)
            {
                $text = htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            print((string)$text);
        }

        /**
         * Alias for the Print method with locale functionality
         *
         * @param mixed $id The ID of the locale string to print
         * @param array $locale Optional, an associative array of parameters to replace in the locale string
         *                      (e.g. ['name' => 'John'] to replace {name} in the locale string)
         * @param bool $escape if True, escapes the HTML encoding from the locale string
         * @throws RuntimeException Thrown if there was an error loading the locale or if the locale key was not found
         */
        public static function printl(mixed $id, array $locale=[], bool $escape=true): void
        {
            self::print(self::resolveLocaleString($id, $locale), $escape);
        }

        /**
         * Returns a locale string instead of printing it, resolving the key exactly like {@see Functions::printl()}:
         * from the active section's locale_id, then the current route's locale_id.
         *
         * @param mixed $id The ID of the locale string
         * @param array $locale Optional, an associative array of parameters to replace in the locale string
         * @param string|null $default Optional value to return when the key is not defined, instead of throwing
         * @return string The (unescaped) locale string
         * @throws RuntimeException Thrown if no locale is loaded or the key was not found and no default was given
         */
        public static function getl(mixed $id, array $locale=[], ?string $default=null): string
        {
            try
            {
                return self::resolveLocaleString($id, $locale);
            }
            catch (RuntimeException $e)
            {
                if ($default !== null)
                {
                    return $default;
                }

                throw $e;
            }
        }

        /**
         * Checks if a locale string is defined for the active section or the current route's locale_id.
         *
         * @param mixed $id The ID of the locale string
         * @return bool True if the key is defined
         */
        public static function hasl(mixed $id): bool
        {
            $currentLocale = WebSession::getLocale();
            $localeId = self::getActiveLocaleId();
            return $currentLocale !== null && $localeId !== null && $currentLocale->hasKey($localeId, (string)$id);
        }

        /**
         * Returns the locale section that printl()/getl() currently resolve from: the active section set by
         * {@see Functions::loadLocalization()} or {@see Functions::insertSection()}, or the current route's locale_id.
         *
         * @return string|null The locale section ID, or null if none is active
         */
        public static function getActiveLocaleId(): ?string
        {
            return self::$activeLocaleSection ?? WebSession::getCurrentRoute()?->getLocaleId();
        }

        /**
         * Resolves a locale string for printl() and getl().
         *
         * @param mixed $id The ID of the locale string
         * @param array $locale Placeholder replacements
         * @return string The locale string, or the key itself when no locale section is available at all
         * @throws RuntimeException Thrown if no locale is loaded or the key was not found
         */
        private static function resolveLocaleString(mixed $id, array $locale): string
        {
            $currentLocale = WebSession::getLocale();
            if ($currentLocale === null)
            {
                throw new RuntimeException('No locale is loaded for the current session');
            }

            // Section context takes priority; fall back to the current route's locale_id
            $localeId = self::getActiveLocaleId();

            if ($localeId === null)
            {
                // Fallback: use the first available locale section from the locale data
                $localeIds = $currentLocale->getLocaleIds();
                if (!empty($localeIds))
                {
                    $localeId = $localeIds[0];
                }

                // Last resort: use the key as-is instead of throwing
                if ($localeId === null)
                {
                    return (string)$id;
                }
            }

            $string = $currentLocale->getString($localeId, (string)$id, $locale);
            if ($string === null)
            {
                throw new RuntimeException(sprintf('Locale key "%s" not found in locale "%s" for locale_id "%s"', $id, $currentLocale->getLocaleCode(), $localeId));
            }

            return $string;
        }

        /**
         * Prints a hidden form field carrying the session's CSRF token ({@see WebSession::getCsrfToken()}).
         * Prints nothing when cookie sessions are disabled.
         *
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         */
        public static function csrfField(?string $cookieName=null): void
        {
            $token = WebSession::getCsrfToken($cookieName);
            if ($token === null)
            {
                return;
            }

            print('<input type="hidden" name="' . WebSession::CSRF_FIELD_NAME . '" value="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">');
        }

        /**
         * Prints a `<meta name="csrf-token">` tag carrying the session's CSRF token. With $attachToRequests, also
         * prints a script that sends the token in the X-CSRF-Token header of same-origin XMLHttpRequest and fetch()
         * requests other than GET, HEAD and OPTIONS. Prints nothing when cookie sessions are disabled.
         *
         * @param bool $attachToRequests Whether to also print the script that attaches the token to requests
         * @param string|null $cookieName Optional cookie name of the session. Defaults to the configured cookie name.
         */
        public static function csrfMeta(bool $attachToRequests=false, ?string $cookieName=null): void
        {
            $token = WebSession::getCsrfToken($cookieName);
            if ($token === null)
            {
                return;
            }

            print('<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">');
            if (!$attachToRequests)
            {
                return;
            }

            $header = json_encode(WebSession::CSRF_HEADER_NAME);
            print(<<<HTML
<script>
(function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    var token = meta ? meta.getAttribute('content') : '';
    var header = {$header};
    function needsToken(method, url) {
        return token && ['GET', 'HEAD', 'OPTIONS'].indexOf(String(method || 'GET').toUpperCase()) === -1
            && new URL(url, window.location.href).origin === window.location.origin;
    }
    var open = XMLHttpRequest.prototype.open, send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.open = function (method, url) {
        this.dwCsrf = needsToken(method, url);
        return open.apply(this, arguments);
    };
    XMLHttpRequest.prototype.send = function () {
        if (this.dwCsrf) { this.setRequestHeader(header, token); }
        return send.apply(this, arguments);
    };
    if (window.fetch) {
        var originalFetch = window.fetch;
        window.fetch = function (input, init) {
            var request = new Request(input, init);
            if (needsToken(request.method, request.url) && !request.headers.has(header)) {
                request.headers.set(header, token);
            }
            return originalFetch.call(this, request);
        };
    }
})();
</script>
HTML);
        }

        /**
         * Generates and prints a root-relative URL for a named route.
         *
         * Resolves the route by its ID, builds a root-relative path from base_path and the route
         * path (no scheme/host, so the browser resolves the origin), substitutes any {variable}
         * placeholders in the route path with values from $pathVariables, and appends optional GET
         * query parameters.
         *
         * @param string $id The route ID as defined in the web configuration.
         * @param array $pathVariables Associative array of path variable substitutions (e.g. ['id' => '42']).
         * @param array $queryParams Associative array of query strigng parameters to append (e.g. ['page' => '2']).
         * @throws RuntimeException Thrown if the route cannot be resolved
         */
        public static function printRoute(string $id, array $pathVariables = [], array $queryParams = []): void
        {
            print(self::getRouteUrl($id, $pathVariables, $queryParams));
        }

        /**
         * Generates a root-relative URL for a named route.
         *
         * Resolves the route by its ID, builds a root-relative path from base_path and the route
         * path (no scheme/host, so the browser resolves the origin), substitutes any {variable}
         * placeholders in the route path with values from $pathVariables, and appends optional GET
         * query parameters.
         *
         * @param string $id The route ID as defined in the web configuration.
         * @param array $pathVariables Associative array of path variable substitutions (e.g. ['id' => '42']).
         * @param array $queryParams Associative array of query strigng parameters to append (e.g. ['page' => '2']).
         * @return string The generated URL for the specified route
         * @throws RuntimeException Thrown if the route cannot be resolved
         */
        public static function getRouteUrl(string $id, array $pathVariables = [], array $queryParams = []): string
        {
            $instance = WebSession::getInstance();
            if ($instance === null)
            {
                throw new RuntimeException(sprintf('Cannot resolve route "%s": no active web session', $id));
            }

            $router = $instance->getWebConfiguration()->getRouter();
            $route  = $router->getRouteById($id);

            if ($route === null)
            {
                throw new RuntimeException(sprintf('Route with ID "%s" is not defined in the web configuration', $id));
            }

            // Build a ROOT-RELATIVE path (base_path + route path). Scheme/host are intentionally
            // omitted so the browser resolves them against the current origin; this avoids leaking
            // an internal host/port and works transparently behind proxies and tunnels.
            $basePath = rtrim($router->getBasePath(), '/');
            $path     = $route->getPath();

            // Substitute {variable} placeholders with provided values
            foreach ($pathVariables as $key => $value)
            {
                $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
            }

            $url = $basePath . $path;
            if (!str_starts_with($url, '/'))
            {
                $url = '/' . $url;
            }

            if (!empty($queryParams))
            {
                $url .= '?' . http_build_query($queryParams);
            }

            return $url;
        }

        /**
         * Generates an absolute URL (scheme and host of the current request) for a named route, for places that
         * need a full address such as canonical links, Open Graph tags or e-mails.
         *
         * @param string $id The route ID as defined in the web configuration.
         * @param array $pathVariables Associative array of path variable substitutions (e.g. ['id' => '42']).
         * @param array $queryParams Associative array of query string parameters to append (e.g. ['page' => '2']).
         * @return string The absolute URL for the specified route
         * @throws RuntimeException Thrown if the route cannot be resolved or there is no current request
         */
        public static function getAbsoluteRouteUrl(string $id, array $pathVariables = [], array $queryParams = []): string
        {
            $path = self::getRouteUrl($id, $pathVariables, $queryParams);
            $request = WebSession::getRequest();
            if ($request === null)
            {
                throw new RuntimeException(sprintf('Cannot build an absolute URL for route "%s": no current request', $id));
            }

            return ($request->isSecure() ? 'https' : 'http') . '://' . rtrim($request->getHost(), '/') . $path;
        }

        /**
         * Renders a PHTML section file and outputs its content.
         *
         * The section can call loadLocalization() to set its own locale scope,
         * which will be automatically restored after the section finishes rendering.
         *
         * @param string $path The absolute path to the PHTML section file.
         * @throws RuntimeException If executing the section throws an error or if the file is not found
         */
        public static function insertSection(string $path, ?string $localizationId = null): void
        {
            // Resolve relative paths against the calling file's directory
            if (strlen($path) > 0 && $path[0] !== '/' && $path[0] !== '\\' && !str_contains($path, '://'))
            {
                $baseDir = ExecutionHandler::getCurrentExecutingDirectory();
                if ($baseDir !== null)
                {
                    $path = $baseDir . DIRECTORY_SEPARATOR . $path;
                }
                else
                {
                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
                    if (isset($trace[1]['file']))
                    {
                        $path = dirname($trace[1]['file']) . DIRECTORY_SEPARATOR . $path;
                    }
                }
            }

            if (!file_exists($path))
            {
                throw new RuntimeException(sprintf('Section file not found at "%s"', $path));
            }

            $previous  = self::$activeLocaleSection;
            $startTime = microtime(true);

            if ($localizationId !== null)
            {
                self::$activeLocaleSection = $localizationId;
            }

            try
            {
                print(ExecutionHandler::executePhtml($path));
            }
            catch (ExecutionException $e)
            {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
            finally
            {
                $duration = microtime(true) - $startTime;
                self::$activeLocaleSection = $previous;
                DebugPanel::trackSectionExecution($path, $duration);
            }
        }

        /**
         * Sets the active locale context for subsequent printl() calls.
         *
         * Call this at the beginning of a section template to make printl()
         * resolve strings from the given locale section ID.
         *
         * @param string $name The locale section ID (e.g., "navbar").
         */
        public static function loadLocalization(string $name): void
        {
            self::$activeLocaleSection = $name;
        }
    }

