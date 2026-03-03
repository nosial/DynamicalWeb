<?php

    namespace DynamicalWeb;

    use DynamicalWeb\Classes\Apcu;
    use DynamicalWeb\Classes\Router;
    use DynamicalWeb\Exceptions\LocaleException;
    use DynamicalWeb\Objects\Locale;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use Exception;
    use Symfony\Component\Yaml\Exception\ParseException;
    use Symfony\Component\Yaml\Yaml;
    use Throwable;

    class WebSession
    {
        private static ?DynamicalWeb $instance=null;
        private static ?Request $request=null;
        private static ?Response $response=null;
        private static ?string $module=null;
        private static ?Route $currentRoute=null;
        private static ?Locale $locale=null;
        private static ?Throwable $exception=null;
        private static array $localeFileCache = [];

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

            $routeResult = Router::findRouteWithDetails(
                webConfiguration: self::$instance->getWebConfiguration(),
                request: self::$request,
                webRootPath: self::$instance->getWebRootPath(),
                webResourcesPath: self::$instance->getWebResourcesPath()
            );

            self::$module = $routeResult->getModule();
            self::$currentRoute = $routeResult->getRoute();
            self::$exception = null;
            self::loadLocale();
        }

        /**
         * Ends the session by clearing all static state.
         */
        public static function endSession(): void
        {
            self::$instance = null;
            self::$request = null;
            self::$response = null;
            self::$module = null;
            self::$currentRoute = null;
            self::$locale = null;
            self::$exception = null;
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
            if ($cookieLocale !== null && is_string($cookieLocale))
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
