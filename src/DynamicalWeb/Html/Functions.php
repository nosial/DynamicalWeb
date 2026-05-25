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
            $currentLocale = WebSession::getLocale();
            if ($currentLocale === null)
            {
                throw new RuntimeException('No locale is loaded for the current session');
            }

            // Section context takes priority; fall back to the current route's locale_id
            $localeId = self::$activeLocaleSection ?? WebSession::getCurrentRoute()?->getLocaleId();
            if ($localeId === null)
            {
                throw new RuntimeException('No active locale section is set for the current execution context');
            }

            $string = $currentLocale->getString($localeId, (string)$id, $locale);
            if ($string === null)
            {
                throw new RuntimeException(sprintf('Locale key "%s" not found in locale "%s" for locale_id "%s"', $id, $currentLocale->getLocaleCode(), $localeId));
            }

            self::print($string, $escape);
        }

        /**
         * Generates and prints a fully-qualified URL for a named route.
         *
         * Resolves the route by its ID, builds the URL from the automatically detected base URL
         * (scheme + host from the current request) and base_path, substitutes any {variable} placeholders in the route
         * path with values from $pathVariables, and appends optional GET query parameters.
         *
         * @param string $id The route ID as defined in the web configuration.
         * @param array $pathVariables Associative array of path variable substitutions (e.g. ['id' => '42']).
         * @param array $queryParams Associative array of query strigng parameters to append (e.g. ['page' => '2']).
         * @throws RuntimeException Thrown if the route cannot be resolved
         */
        public static function printRoute(string $id, array $pathVariables = [], array $queryParams = []): void
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

            // Auto-detect the base URL from the current request's scheme, host and port
            $request  = WebSession::getRequest();
            $baseUrl  = ($request->isSecure() ? 'https' : 'http') . '://' . rtrim($request->getHost(), '/');
            $basePath = rtrim($router->getBasePath(), '/');
            $path     = $route->getPath();

            // Substitute {variable} placeholders with provided values
            foreach ($pathVariables as $key => $value)
            {
                $path = str_replace('{' . $key . '}', rawurlencode((string) $value), $path);
            }

            $url = $baseUrl . $basePath . $path;

            if (!empty($queryParams))
            {
                $url .= '?' . http_build_query($queryParams);
            }

            print($url);
        }

        /**
         * Renders a named section (a reusable .phtml fragment) and outputs its content.
         *
         * The section's locale_id (if configured) overrides the current route's locale_id for
         * the duration of the section's rendering, so printl() calls inside the section
         * resolve strings from the section's own locale scope without conflict.
         *
         * @param string $name The section name as defined under `sections:` in the web configuration.
         * @throws RuntimeException If executing the section throws an error or if the section is not configured or its
         *                          module file is not found
         */
        public static function insertSection(string $name): void
        {
            $instance = WebSession::getInstance();
            if ($instance === null)
            {
                throw new RuntimeException(sprintf('Cannot insert section "%s": no active web session', $name));
            }

            $section = $instance->getSection($name);
            if ($section === null)
            {
                throw new RuntimeException(sprintf('Section "%s" is not defined in the web configuration', $name));
            }

            $modulePath = $instance->getSectionModulePath($name);
            if ($modulePath === null || !file_exists($modulePath))
            {
                throw new RuntimeException(sprintf('Module file for section "%s" not found at "%s"', $name, $modulePath ?? 'unknown'));
            }

            // Push this section's locale_id onto the context stack; restore after execution
            $previous  = self::$activeLocaleSection;
            $startTime = microtime(true);
            self::$activeLocaleSection = $section->getLocaleId();

            try
            {
                print(ExecutionHandler::executePhtml($modulePath));
            }
            catch (ExecutionException $e)
            {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }
            finally
            {
                $duration = microtime(true) - $startTime;
                self::$activeLocaleSection = $previous;
                DebugPanel::trackSectionExecution($name, $duration);
            }
        }
    }

