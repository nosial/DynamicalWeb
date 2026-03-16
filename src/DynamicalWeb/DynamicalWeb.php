<?php

    namespace DynamicalWeb;

    use DynamicalWeb\Classes\Apcu;
    use DynamicalWeb\Classes\DebugPanel;
    use DynamicalWeb\Classes\ExecutionHandler;
    use DynamicalWeb\Classes\PathResolver;
    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\PathConstants;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Exceptions\DynamicalWebException;
    use DynamicalWeb\Exceptions\ExecutionException;
    use DynamicalWeb\Objects\WebConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Section;
    use ncc\Runtime;
    use Symfony\Component\Yaml\Yaml;
    use Throwable;

    class DynamicalWeb
    {
        private string $package;
        private ?string $configurationPath;
        private string $webRootPath;
        private string $webResourcesPath;
        private array $availableLocalesPaths;
        private array $availableLocales;
        private WebConfiguration $webConfiguration;

        /**
         * DynamicalWeb Instance Constructor
         *
         * @param string $package The name of the package to load the configuration from
         * @throws DynamicalWebException Thrown if the package is not imported or the configuration file does not exist
         */
        public function __construct(string $package)
        {
            if(!Runtime::isImported($package))
            {
                throw new DynamicalWebException(sprintf('Package "%s" is not imported', $package));
            }

            if(!isset(Runtime::getImportedPackageOptions($package)['web_configuration']))
            {
                throw new DynamicalWebException(sprintf('Package "%s" must be built with a "web_configuration" option pointing to the configuration file', $package));
            }

            $this->package = $package;
            $configurationOption = Runtime::getImportedPackageOptions($package)['web_configuration'];

            // Check if configuration is a string (file path) or array (direct configuration)
            if(is_string($configurationOption))
            {
                $this->configurationPath = sprintf("ncc://%s/%s", $package, $configurationOption);

                if(!file_exists($this->configurationPath))
                {
                    throw new DynamicalWebException(sprintf('Configuration file "%s" does not exist in package "%s"', $this->configurationPath, $package));
                }

                // Try APCu cache before hitting the filesystem
                $configCacheKey = 'dw_webcfg_' . md5($this->configurationPath);
                $cachedData = Apcu::fetch($configCacheKey, $cfgHit);
                if ($cfgHit && is_array($cachedData))
                {
                    $this->webConfiguration = new WebConfiguration($cachedData);
                }
                else
                {
                    $parsedData = Yaml::parseFile($this->configurationPath);
                    $this->webConfiguration = new WebConfiguration($parsedData);
                    // Only cache when APCu is not disabled by the configuration itself
                    if (!$this->webConfiguration->getApplication()->isApcuDisabled())
                    {
                        Apcu::store($configCacheKey, $parsedData, $this->webConfiguration->getApplication()->getApcuConfigTtl());
                    }
                }
            }
            elseif(is_array($configurationOption))
            {
                $this->configurationPath = null;
                $this->webConfiguration = new WebConfiguration($configurationOption);
            }
            else
            {
                throw new DynamicalWebException(sprintf('Invalid configuration type for package "%s". Must be a string (file path) or array', $package));
            }

            $this->webRootPath = sprintf("ncc://%s/%s", $package, $this->webConfiguration->getApplication()->getRoot());
            $this->webResourcesPath = sprintf("ncc://%s/%s", $package, $this->webConfiguration->getApplication()->getResources());
            $this->availableLocalesPaths = [];
            $this->availableLocales = [];

            // Check for new-style locales configuration (direct file paths)
            if($this->webConfiguration->getLocales() !== null)
            {
                foreach($this->webConfiguration->getLocales() as $localeCode => $localePath)
                {
                    $this->availableLocales[$localeCode] = sprintf("ncc://%s/%s", $package, $localePath);
                }
            }
        }

        /**
         * Returns the name of the package this DynamicalWeb instance is associated with.
         *
         * @return string The name of the package, eg; "com.example.myapp"
         */
        public function getPackage(): string
        {
            return $this->package;
        }

        /**
         * Returns the full path to the configuration file used by this DynamicalWeb instance.
         *
         * @return string|null The full path to the configuration file, eg; "ncc://com.example.myapp/config/web.yaml", or null if configuration is from array
         */
        public function getConfigurationPath(): ?string
        {
            return $this->configurationPath;
        }

        /**
         * Returns the full path to the web root directory for this DynamicalWeb instance.
         *
         * @return string The full path to the web root directory, eg; "ncc://com.example.myapp/public"
         */
        public function getWebRootPath(): string
        {
            return $this->webRootPath;
        }

        /**
         * Returns the full path to the web resources directory for this DynamicalWeb instance.
         *
         * @return string The full path to the web resources directory, eg; "ncc://com.example.myapp/resources"
         */
        public function getWebResourcesPath(): string
        {
            return $this->webResourcesPath;
        }

        /**
         * Returns an associative array of available locales where the key is the ISO 639-1 language code
         * and the value is the full path to the locale .yml file.
         *
         * @return array An associative array of locale codes to file paths
         */
        public function getAvailableLocales(): array
        {
            return $this->availableLocales;
        }

        /**
         * Returns the WebConfiguration object associated with this DynamicalWeb instance.
         *
         * @return WebConfiguration The WebConfiguration object
         */
        public function getWebConfiguration(): WebConfiguration
        {
            return $this->webConfiguration;
        }

        /**
         * Returns an array of full paths to the available locale .yml files.
         *
         * @return array An array of full paths to locale files
         */
        public function getAvailableLocalesPaths(): array
        {
            return $this->availableLocalesPaths;
        }

        /**
         * Returns an array of available locale codes (ISO 639-1 language codes).
         *
         * @return array An array of available locale codes
         */
        public function getAvailableLocaleCodes(): array
        {
            return array_keys($this->availableLocales);
        }

        /**
         * Retrieves the full path to the locale .yml file for the specified locale code.
         *
         * @param string $locale The ISO 639-1 language code of the locale to retrieve
         * @return string|null The full path to the locale file if found, or null if not found
         */
        public function getLocaleFilePath(string $locale): ?string
        {
            return $this->availableLocales[$locale] ?? null;
        }

        /**
         * Returns all configured sections from the web configuration.
         *
         * @return array<string, Section>
         */
        public function getSections(): array
        {
            return $this->webConfiguration->getSections();
        }

        /**
         * Returns a specific section by name, with its module path resolved to an absolute path.
         *
         * @param string $name
         * @return Section|null
         */
        public function getSection(string $name): ?Section
        {
            return $this->webConfiguration->getSection($name);
        }

        /**
         * Resolves the absolute path to a section's module file.
         *
         * @param string $name
         * @return string|null Absolute path to the .phtml file, or null if the section is not found
         */
        public function getSectionModulePath(string $name): ?string
        {
            $section = $this->getSection($name);
            if ($section === null)
            {
                return null;
            }

            return $this->buildModulePath($section->getModule());
        }

        /**
         * Builds an absolute module path by joining the web root path with a relative module path.
         *
         * @param string $module Relative module path (leading slash is optional)
         * @return string Absolute path to the module file
         */
        private function buildModulePath(string $module): string
        {
            return rtrim($this->webRootPath, '/') . '/' . ltrim($module, '/');
        }

        /**
         * Handles the incoming HTTP request by routing it to the appropriate module based on the configuration and
         * sending the response back to the client.
         *
         * @return void
         */
        public function handleRequest(): void
        {
            // Enable execution tracking if debug mode is enabled
            if($this->webConfiguration->getApplication()->isDebugPanelEnabled())
            {
                DebugPanel::start();
            }

            try
            {
                WebSession::startSession($this);

                $preRequests = $this->webConfiguration->getApplication()->getPreRequest();
                if($preRequests !== null && count($preRequests) > 0)
                {
                    foreach($preRequests as $preRequestModule)
                    {
                        $preRequestModulePath = $this->buildModulePath($preRequestModule);
                        if (file_exists($preRequestModulePath))
                        {
                            ExecutionHandler::executePhp($preRequestModulePath);
                        }
                    }
                }

                if(!$this->webConfiguration->getApplication()->isDefaultHeadersDisabled())
                {
                    WebSession::getResponse()->setHeader('X-Powered-By', 'DynamicalWeb');
                    WebSession::getResponse()->setHeader('X-Request-ID', WebSession::getRequest()->getId());
                }

                $modulePath = WebSession::getModule();
                if($modulePath === null)
                {
                    $this->handleNotFoundResponse();
                }
                else
                {
                    $this->executeModule($modulePath);
                }


                $postRequests = $this->webConfiguration->getApplication()->getPostRequest();
                if($postRequests !== null && count($postRequests) > 0)
                {
                    foreach($postRequests as $postRequestModule)
                    {
                        $postRequestModulePath = $this->buildModulePath($postRequestModule);
                        if (file_exists($postRequestModulePath))
                        {
                            ExecutionHandler::executePhp($postRequestModulePath);
                        }
                    }
                }

            }
            catch (ExecutionException $e)
            {
                $this->safeHandleErrorResponse($e);
            }
            catch (Throwable $e)
            {
                $this->safeHandleErrorResponse(new ExecutionException('Unexpected error occurred: ' . $e->getMessage(), 0, $e));
            }
            finally
            {
                // Inject debug panel before sending response if enabled
                try
                {
                    if($this->webConfiguration->getApplication()->isDebugPanelEnabled() && WebSession::getResponse() !== null)
                    {
                        DebugPanel::inject(WebSession::getResponse(), WebSession::getRequest(), WebSession::getCurrentRoute());
                    }
                }
                catch (Throwable)
                {
                    // Debug panel injection failed, proceed to send response
                }

                try
                {
                    $response = WebSession::getResponse();
                    if ($response !== null)
                    {
                        $response->send();
                    }
                }
                catch (Throwable)
                {
                    // Response sending failed
                }

                WebSession::endSession();
            }
        }

        /**
         * Executes the specified module based on its file extension. If the module is a .phtml file, it will be executed
         *
         * @param string $module The full path to the module to execute, eg; "ncc://com.example.myapp/public/index.phtml"
         * @throws ExecutionException Thrown if there is an error during module execution
         */
        private function executeModule(string $module): void
        {
            if ($module === null || !file_exists($module))
            {
                $this->handleNotFoundResponse();
            }
            else
            {
                switch(pathinfo($module, PATHINFO_EXTENSION))
                {
                    case 'phtml':
                        $output = ExecutionHandler::executePhtml($module);
                        WebSession::getResponse()->setContentType('text/html');
                        WebSession::getResponse()->setBody($output);
                        break;
                    case 'php':
                        ExecutionHandler::executePhp($module);
                        break;

                    default:
                        $this->handleStaticResponse($module);
                        return;
                }
            }
        }

        /**
         * Handles serving static files by setting appropriate headers and content type based on the file extension.
         *
         * @param string $filePath The full path to the static file to serve
         */
        private function handleStaticResponse(string $filePath): void
        {
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeType  = MimeType::fromExtension($extension);
            $appConfig = $this->webConfiguration->getApplication();

            // Cache filemtime + filesize per file to avoid repeated syscalls across requests
            $metaCacheKey = 'dw_filemeta_' . md5($filePath);
            $meta = Apcu::fetch($metaCacheKey, $metaHit);
            if (!$metaHit || !is_array($meta))
            {
                $meta = ['mtime' => filemtime($filePath), 'size' => filesize($filePath)];
                Apcu::store($metaCacheKey, $meta, $appConfig->getApcuMetaTtl());
            }

            $lastModified = $meta['mtime'];

            WebSession::getResponse()->setContentType($mimeType->value);
            WebSession::getResponse()->setHeader('Content-Length', (string) $meta['size']);
            WebSession::getResponse()->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');

            $cacheMaxAge = $appConfig->getStaticCacheMaxAge();
            if($cacheMaxAge > 0)
            {
                WebSession::getResponse()->setHeader('Cache-Control', 'public, max-age=' . $cacheMaxAge);
            }

            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
            {
                $ifModifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($ifModifiedSince !== false && $ifModifiedSince >= $lastModified)
                {
                    WebSession::getResponse()->setStatusCode(ResponseCode::NOT_MODIFIED);
                    return;
                }
            }

            // For small files, serve content from APCu cache to avoid disk I/O on every request.
            // Entries are stored with the file's mtime; a mtime mismatch invalidates the cached content.
            if ($meta['size'] <= $appConfig->getApcuContentMaxSize())
            {
                $contentCacheKey = 'dw_filecontent_' . md5($filePath);
                $cached = Apcu::fetch($contentCacheKey, $contentHit);

                if ($contentHit && is_array($cached) && $cached['mtime'] === $lastModified)
                {
                    WebSession::getResponse()->setBody($cached['content']);
                    return;
                }

                $content = file_get_contents($filePath);
                if ($content !== false)
                {
                    Apcu::store($contentCacheKey, ['mtime' => $lastModified, 'content' => $content], $appConfig->getApcuContentTtl());
                    WebSession::getResponse()->setBody($content);
                    return;
                }
            }

            // Large files (or APCu unavailable): stream from disk
            WebSession::getResponse()->setFileDownload($filePath, basename($filePath));
            WebSession::getResponse()->removeHeader('Content-Disposition');
        }

        /**
         * Handles an error response by attempting to use a custom error handler if configured, and falling back to a default error message if not.
         *
         * @param ExecutionException $e The exception that was thrown during module execution
         */
        private function handleErrorResponse(ExecutionException $e): void
        {
            WebSession::setException($e);
            $errorHandler = $this->webConfiguration->getRouter()->getResponseHandler(ResponseCode::INTERNAL_SERVER_ERROR);
            if ($errorHandler !== null)
            {
                $errorHandlerPath = $this->buildModulePath($errorHandler);
                
                if (file_exists($errorHandlerPath))
                {
                    try
                    {
                        $output = ExecutionHandler::executePhtml($errorHandlerPath);
                        WebSession::getResponse()->setStatusCode(ResponseCode::INTERNAL_SERVER_ERROR);
                        WebSession::getResponse()->setContentType(MimeType::HTML);
                        WebSession::getResponse()->setBody($output);
                        return;
                    }
                    catch (ExecutionException)
                    {
                        // Error handler itself failed, fall through to builtin error page
                    }
                }
            }
            
            $builtinErrorPage = PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, '500.phtml');
            if (file_exists($builtinErrorPage))
            {
                try
                {
                    WebSession::getResponse()->setStatusCode(ResponseCode::INTERNAL_SERVER_ERROR);
                    WebSession::getResponse()->setContentType(MimeType::HTML);
                    WebSession::getResponse()->setBody(ExecutionHandler::executePhtml($builtinErrorPage));
                    return;
                }
                catch (ExecutionException)
                {
                    // Builtin page failed, fall through to plain text
                }
            }
            
            WebSession::getResponse()->setStatusCode(ResponseCode::INTERNAL_SERVER_ERROR);
            WebSession::getResponse()->setContentType(MimeType::TEXT);
            
            if ($this->webConfiguration->getApplication()->errorReportingEnabled())
            {
                $errorMessage = "500 Internal Server Error\n\n";
                $errorMessage .= $e->getMessage() . "\n\n";
                if ($e->getPrevious() !== null)
                {
                    $errorMessage .= "Caused by: " . $e->getPrevious()->getMessage() . "\n";
                    $errorMessage .= $e->getPrevious()->getTraceAsString();
                }
                else
                {
                    $errorMessage .= $e->getTraceAsString();
                }
                
                WebSession::getResponse()->setBody($errorMessage);
            }
            else
            {
                WebSession::getResponse()->setBody('500 Internal Server Error');
            }
        }

        /**
         * Safely handles an error response with multiple fallback layers. Ensures a Response object
         * exists before attempting error handling, and falls back to raw PHP output if all else fails.
         *
         * @param ExecutionException $e The exception that triggered the error response
         */
        private function safeHandleErrorResponse(ExecutionException $e): void
        {
            WebSession::ensureResponse();

            try
            {
                $this->handleErrorResponse($e);
            }
            catch (Throwable)
            {
                // handleErrorResponse itself failed — set a minimal 500 response
                try
                {
                    WebSession::getResponse()->setStatusCode(ResponseCode::INTERNAL_SERVER_ERROR);
                    WebSession::getResponse()->setContentType(MimeType::TEXT);

                    if ($this->webConfiguration->getApplication()->errorReportingEnabled())
                    {
                        $errorMessage = "500 Internal Server Error\n\n";
                        $errorMessage .= $e->getMessage() . "\n\n";
                        if ($e->getPrevious() !== null)
                        {
                            $errorMessage .= "Caused by: " . $e->getPrevious()->getMessage() . "\n";
                            $errorMessage .= $e->getPrevious()->getTraceAsString();
                        }
                        else
                        {
                            $errorMessage .= $e->getTraceAsString();
                        }

                        WebSession::getResponse()->setBody($errorMessage);
                    }
                    else
                    {
                        WebSession::getResponse()->setBody('500 Internal Server Error');
                    }
                }
                catch (Throwable)
                {
                    // Absolute last resort — direct PHP output
                    if (!headers_sent())
                    {
                        http_response_code(500);
                        header('Content-Type: text/plain');
                    }

                    echo '500 Internal Server Error';
                }
            }
        }

        /**
         * Handles a 404 Not Found response by attempting to use a custom error handler if configured, and falling back
         * to a default error message if not.
         */
        private function handleNotFoundResponse(): void
        {
            $errorHandler = $this->webConfiguration->getRouter()->getResponseHandler(ResponseCode::NOT_FOUND);
            if ($errorHandler !== null)
            {
                $errorHandlerPath = $this->buildModulePath($errorHandler);
                if (file_exists($errorHandlerPath))
                {
                    try
                    {
                        $output = ExecutionHandler::executePhtml($errorHandlerPath);
                        WebSession::getResponse()->setStatusCode(ResponseCode::NOT_FOUND);
                        WebSession::getResponse()->setContentType(MimeType::HTML);
                        WebSession::getResponse()->setBody($output);
                        return;
                    }
                    catch (ExecutionException $e)
                    {
                        $this->handleErrorResponse($e);
                        return;
                    }
                }
            }

            $builtinNotFoundPage = PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, '404.phtml');
            if (file_exists($builtinNotFoundPage))
            {
                try
                {
                    WebSession::getResponse()->setStatusCode(ResponseCode::NOT_FOUND);
                    WebSession::getResponse()->setContentType(MimeType::HTML);
                    WebSession::getResponse()->setBody(ExecutionHandler::executePhtml($builtinNotFoundPage));
                    return;
                }
                catch (ExecutionException)
                {
                    // Builtin page failed, fall through to plain text
                }
            }

            WebSession::getResponse()->setStatusCode(ResponseCode::NOT_FOUND);
            WebSession::getResponse()->setContentType(MimeType::TEXT);
            WebSession::getResponse()->setBody('404 Not Found');
        }

        /**
         * Retrieves the version of the DynamicalWeb package from the runtime.
         *
         * @return string The version string of the DynamicalWeb package, or 'unknown' if it cannot be determined
         */
        public static function getVersion(): string
        {
            return Runtime::getImportedPackage('net.nosial.dynamicalweb')?->getAssembly()->getVersion() ?? 'unknown';
        }
    }
