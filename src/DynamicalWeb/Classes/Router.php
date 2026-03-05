<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Enums\PathConstants;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\RouteResult;
    use DynamicalWeb\Objects\WebConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\Objects\WebConfiguration\RouterConfiguration;

    class Router
    {
        private const string BUILTIN_RESOURCE_PREFIX = '/dynaweb/';

        private static array $regexCache = [];
        private static array $resourceExistenceCache = [];

        /**
         * Finds the appropriate route for the request and returns both the module path and route object.
         *
         * @param WebConfiguration $webConfiguration The web configuration containing routing rules
         * @param Request $request The incoming HTTP request
         * @param string $webRootPath The web root path
         * @param string $webResourcesPath The web resources path
         * @return RouteResult Result containing module path and route object
         */
        public static function findRouteWithDetails(WebConfiguration $webConfiguration, Request $request, string $webRootPath, string $webResourcesPath): RouteResult
        {
            $routerConfiguration = $webConfiguration->getRouter();
            $requestPath   = self::normalizePath($request->getPath(), $routerConfiguration->getBasePath());
            $requestMethod = $request->getMethod()->value;

            // Built-in /dynaweb/* paths are never APCu-cached (dynamic/debug endpoints)
            if (str_starts_with($requestPath, self::BUILTIN_RESOURCE_PREFIX))
            {
                return self::resolveBuiltinPath($requestPath, $webConfiguration, $webRootPath);
            }

            // Special handling for /dynaweb root
            if ($requestPath === '/dynaweb')
            {
                return new RouteResult(
                    PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'about.phtml'),
                    null
                );
            }

            // APCu route resolution cache — keyed by app root + method + path
            $appKey    = md5($webRootPath);
            $cacheKey  = 'dw_rtres_' . $appKey . '_' . $requestMethod . '_' . md5($requestPath);
            $cached    = Apcu::fetch($cacheKey, $hit);
            if ($hit && is_array($cached))
            {
                $modulePath = $cached['module'];   // string|null
                $routePath  = $cached['route'];    // string|null
                $route      = $routePath !== null ? $routerConfiguration->getRoute($routePath) : null;
                return new RouteResult($modulePath, $route);
            }

            // Resolve and cache the result
            $result = self::resolveRoute($routerConfiguration, $requestPath, $requestMethod, $webRootPath, $webResourcesPath);
            $route  = $result->getRoute();
            Apcu::store($cacheKey, [
                'module' => $result->getModule(),
                'route'  => $route !== null ? $route->getPath() : null,
            ]);

            return $result;
        }

        /**
         * Handles all /dynaweb/* built-in paths (not APCu-cached).
         *
         * @param string $requestPath The normalized request path starting with /dynaweb/
         * @param WebConfiguration $webConfiguration The web configuration containing application settings (e.g. debug panel enabled)
         * @param string $webRootPath The web root path for resolving module paths
         * @return RouteResult Result containing module path and null route (built-in paths do not have associated Route objects)
         */
        private static function resolveBuiltinPath(string $requestPath, WebConfiguration $webConfiguration, string $webRootPath): RouteResult
        {
            // Debug API routes — only available when debug panel is enabled
            if (str_starts_with($requestPath, self::BUILTIN_RESOURCE_PREFIX . 'debug/'))
            {
                if ($webConfiguration->getApplication()->isDebugPanelEnabled())
                {
                    $debugRoute = self::findDebugApiRoute($requestPath);
                    if ($debugRoute !== null)
                    {
                        return new RouteResult($debugRoute, null);
                    }
                }

                return new RouteResult(null, null);
            }

            // Special handling for /dynaweb/health endpoint
            if ($requestPath === self::BUILTIN_RESOURCE_PREFIX . 'health')
            {
                return new RouteResult(PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'health.php'), null);
            }

            // Special handling for /dynaweb/language/{id} endpoint
            if (preg_match('|^/dynaweb/language/([^/]+)$|', $requestPath))
            {
                return new RouteResult(PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'language.php'), null);
            }

            // Handle other built-in resources
            $builtinResource = self::findBuiltinResource($requestPath);
            if ($builtinResource !== null)
            {
                return new RouteResult($builtinResource, null);
            }

            return new RouteResult(null, null);
        }

        /**
         * Core route resolution logic (called when APCu misses).
         *
         * @param RouterConfiguration $routerConfiguration The router configuration containing defined routes and response handlers
         * @param string $requestPath The normalized request path to resolve
         * @param string $requestMethod The HTTP method of the request (e.g. GET, POST, etc.)
         * @param string $webRootPath The web root path for resolving module paths
         * @param string $webResourcesPath The path to static web resources for resolving static file requests
         * @return RouteResult Result containing the resolved module path (if any) and the associated Route object (if any)
         */
        private static function resolveRoute(
            RouterConfiguration $routerConfiguration,
            string              $requestPath,
            string              $requestMethod,
            string              $webRootPath,
            string              $webResourcesPath
        ): RouteResult
        {
            // First try exact match (faster)
            $route = $routerConfiguration->getRoute($requestPath);
            if ($route !== null && self::isMethodAllowed($route, $requestMethod))
            {
                return new RouteResult(self::resolveModulePath($webRootPath, $route->getModule()), $route);
            }

            // Try pattern matching for dynamic routes
            foreach ($routerConfiguration->getRoutes() as $route)
            {
                $routePath = $route->getPath();

                // Skip routes without parameters
                if (!str_contains($routePath, '{'))
                {
                    continue;
                }

                if (preg_match(self::convertRouteToRegex($routePath), $requestPath) === 1)
                {
                    if (self::isMethodAllowed($route, $requestMethod))
                    {
                        return new RouteResult(self::resolveModulePath($webRootPath, $route->getModule()), $route);
                    }
                }
            }

            // Check for static resources
            $staticResource = self::findStaticResource($webResourcesPath, $requestPath);
            if ($staticResource !== null)
            {
                return new RouteResult($staticResource, null);
            }

            // Check for 404 handler
            $notFoundHandler = $routerConfiguration->getResponseHandler(404);
            if ($notFoundHandler !== null)
            {
                return new RouteResult(self::resolveModulePath($webRootPath, $notFoundHandler), null);
            }

            // No route found
            return new RouteResult(null, null);
        }

        /**
         * Defines the built-in debug API routes (only available when debug panel is enabled).
         *
         * @param string $requestPath The normalized request path starting with /dynaweb/debug/
         * @return string|null The ncc:// path to the debug API module if the route is recognized, or null if not found
         */
        private static function findDebugApiRoute(string $requestPath): ?string
        {
            return match($requestPath)
            {
                '/dynaweb/debug/stats' => PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'debug_api_stats.php'),
                default               => null,
            };
        }

        /**
         * Resolves built-in resources under /dynaweb/* (except debug APIs) and returns the ncc:// path if found.
         *
         * @param string $requestPath The normalized request path starting with /dynaweb/
         * @return string|null The ncc:// path to the built-in resource if it exists, or null if not found
         */
        public static function findBuiltinResource(string $requestPath): ?string
        {
            // In-process cache — avoids repeated lookups within the same worker
            if (array_key_exists($requestPath, self::$resourceExistenceCache))
            {
                return self::$resourceExistenceCache[$requestPath] ?: null;
            }

            // APCu cross-process cache
            $cacheKey = 'dw_builtin_res_' . md5($requestPath);
            $cached   = Apcu::fetch($cacheKey, $hit);
            if ($hit && is_string($cached))
            {
                return self::$resourceExistenceCache[$requestPath] = ($cached !== '' ? $cached : null);
            }

            // Security: Prevent directory traversal attacks
            $sanitized = PathResolver::sanitizePath($requestPath);

            // Remove builtin resource prefix
            $resourcePath = substr($sanitized, strlen(self::BUILTIN_RESOURCE_PREFIX));

            // Remove leading slash for file system path construction
            $resourcePath = ltrim($resourcePath, '/');

            // Build the ncc:// path to the built-in WebResources
            $filePath = PathResolver::buildNccPath(
                PathConstants::DYNAMICAL_WEB->value,
                PathConstants::DYNAMICAL_WEB_RESOURCES->value,
                $resourcePath
            );

            // Normalize the path
            $filePath = PathResolver::normalizePath($filePath);

            $result = null;
            if (PathResolver::isValidFile($filePath))
            {
                $expectedPrefix = PathResolver::normalizePath(
                    PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_WEB_RESOURCES->value, '')
                );

                if (str_starts_with($filePath, $expectedPrefix))
                {
                    $result = $filePath;
                }
            }

            Apcu::store($cacheKey, $result ?? '', 60);
            return self::$resourceExistenceCache[$requestPath] = $result;
        }

        /**
         * Resolves static resources under the specified web resources path and returns the file system path if found.
         *
         * @param string $webResourcesPath The base path to the web resources directory
         * @param string $requestPath The normalized request path to resolve as a static resource
         * @return string|null The file system path to the static resource if it exists, or null if not found
         */
        public static function findStaticResource(string $webResourcesPath, string $requestPath): ?string
        {
            $cacheKey2 = $webResourcesPath . '|' . $requestPath;

            // In-process cache
            if (array_key_exists($cacheKey2, self::$resourceExistenceCache))
            {
                return self::$resourceExistenceCache[$cacheKey2] ?: null;
            }

            // APCu cross-process cache
            $apcuKey = 'dw_static_res_' . md5($cacheKey2);
            $cached  = Apcu::fetch($apcuKey, $hit);
            if ($hit && is_string($cached))
            {
                return self::$resourceExistenceCache[$cacheKey2] = ($cached !== '' ? $cached : null);
            }
            // Security: Prevent directory traversal attacks
            $requestPath = PathResolver::sanitizePath($requestPath);

            // Remove leading slash for file system path construction
            $requestPath = ltrim($requestPath, '/');

            // Build the full path by appending request path to webResourcesPath
            $filePath = rtrim($webResourcesPath, '/') . '/' . $requestPath;

            // Normalize the path
            $filePath = PathResolver::normalizePath($filePath);

            $result = null;
            if (PathResolver::isValidFile($filePath))
            {
                $expectedPrefix = rtrim($webResourcesPath, '/') . '/';

                if (str_starts_with($filePath, $expectedPrefix))
                {
                    $result = $filePath;
                }
            }

            Apcu::store($apcuKey, $result ?? '', 60);
            return self::$resourceExistenceCache[$cacheKey2] = $result;
        }

        /**
         * Resolves the full module path by combining the web root path with the module path defined in the route.
         *
         * @param string $webRootPath The base web root path of the application
         * @param string $modulePath The module path defined in the route (relative to web root)
         * @return string The resolved full module path
         */
        private static function resolveModulePath(string $webRootPath, string $modulePath): string
        {
            // Build the full path by appending module path to webRootPath
            $fullPath = rtrim($webRootPath, '/') . '/' . ltrim($modulePath, '/');
            
            // Normalize the path
            $fullPath = PathResolver::normalizePath($fullPath);
            
            return $fullPath;
        }

        /**
         * Extracts path parameters from the request based on the defined routes in the web configuration.
         *
         * @param WebConfiguration $webConfiguration The web configuration containing routing rules
         * @param Request $request The incoming HTTP request
         * @return array An associative array of parameter names and their corresponding values extracted from the request path
         */
        public static function extractPathParameters(WebConfiguration $webConfiguration, Request $request): array
        {
            $routerConfiguration = $webConfiguration->getRouter();
            $requestPath = self::normalizePath($request->getPath(), $routerConfiguration->getBasePath());
            $requestMethod = $request->getMethod()->value;

            // First try exact match - no parameters
            $route = $routerConfiguration->getRoute($requestPath);
            if ($route !== null && self::isMethodAllowed($route, $requestMethod))
            {
                return [];
            }

            // Try pattern matching for dynamic routes
            foreach ($routerConfiguration->getRoutes() as $route)
            {
                $routePath = $route->getPath();
                
                // Skip routes without parameters
                if (!str_contains($routePath, '{'))
                {
                    continue;
                }

                $params = self::matchRouteAndExtractParameters($routePath, $requestPath);
                
                if ($params !== null && self::isMethodAllowed($route, $requestMethod))
                {
                    return $params;
                }
            }

            // No matching route found, return empty array
            return [];
        }

        /**
         * Normalizes the request path by removing the base path, trimming trailing slashes, and ensuring it starts with a slash.
         *
         * @param string $requestPath The original request path from the HTTP request
         * @param string $basePath The base path defined in the router configuration (if any)
         * @return string The normalized request path ready for route matching
         */
        private static function normalizePath(string $requestPath, string $basePath): string
        {
            // Remove base path from request path if it exists
            if (!empty($basePath) && str_starts_with($requestPath, $basePath))
            {
                $requestPath = substr($requestPath, strlen($basePath));
            }

            // Remove trailing slash (except for root)
            if ($requestPath !== '/' && str_ends_with($requestPath, '/'))
            {
                $requestPath = rtrim($requestPath, '/');
            }

            // Ensure path starts with /
            if (!str_starts_with($requestPath, '/'))
            {
                $requestPath = '/' . $requestPath;
            }

            return $requestPath;
        }

        /**
         * Matches the request path against the route path pattern and extracts parameter values if it matches.
         *
         * @param string $routePath The route path pattern defined in the configuration (e.g. /users/{id})
         * @param string $requestPath The normalized request path to match against the route pattern
         * @return array|null An associative array of parameter names and their corresponding values if the route matches, or null if it does not match
         */
        private static function matchRouteAndExtractParameters(string $routePath, string $requestPath): ?array
        {
            // Extract parameter names and their constraints
            preg_match_all('/\{([a-zA-Z0-9_]+)(?::([^}]+))?}/', $routePath, $matches, PREG_SET_ORDER);
            
            $parameterNames = [];
            foreach ($matches as $match)
            {
                $parameterNames[] = $match[1];
            }

            // Create regex pattern and extract values
            $pattern = self::convertRouteToRegex($routePath);
            
            if (preg_match($pattern, $requestPath, $valueMatches))
            {
                // Remove the full match (first element)
                array_shift($valueMatches);
                
                // Combine parameter names with values
                $parameters = [];
                foreach ($parameterNames as $index => $name)
                {
                    $value = $valueMatches[$index] ?? null;
                    
                    // Decode URL-encoded values
                    if ($value !== null)
                    {
                        $value = urldecode($value);
                    }
                    
                    $parameters[$name] = $value;
                }
                
                return $parameters;
            }

            return null;
        }

        /**
         * Checks if the HTTP method of the request is allowed for the given route based on its allowed methods configuration.
         *
         * @param Route $route The route object containing the allowed methods configuration
         * @param string $method The HTTP method of the incoming request (e.g. GET, POST, etc.)
         * @return bool True if the method is allowed for the route, false otherwise
         */
        private static function isMethodAllowed(Route $route, string $method): bool
        {
            $allowedMethods = $route->getAllowedMethods();
            
            // Check for wildcard
            if (in_array('*', $allowedMethods))
            {
                return true;
            }

            // Check if method is in allowed list
            return in_array($method, $allowedMethods);
        }

        /**
         * Converts a route path pattern with parameters (e.g. /users/{id}) into a regular expression for matching request paths.
         * Caches the compiled regex patterns for performance optimization.
         *
         * @param string $route The route path pattern to convert (e.g. /users/{id})
         * @return string The regular expression pattern corresponding to the route path
         */
        private static function convertRouteToRegex(string $route): string
        {
            if (isset(self::$regexCache[$route]))
            {
                return self::$regexCache[$route];
            }

            // APCu cross-process cache for compiled route regex patterns
            $cacheKey = 'dw_route_regex_' . md5($route);
            $cached = Apcu::fetch($cacheKey, $success);
            if ($success && is_string($cached))
            {
                return self::$regexCache[$route] = $cached;
            }

            // Escape special regex characters except {}
            $pattern = preg_quote($route, '/');
            
            // Unescape the curly braces that were escaped by preg_quote
            $pattern = str_replace(['\{', '\}'], ['{', '}'], $pattern);
            
            // Replace parameter placeholders with regex patterns
            // Supports: {param} or {param:constraint}
            $pattern = preg_replace_callback(
                '/\{([a-zA-Z0-9_]+)(?::([^}]+))?}/',
                function($matches)
                {
                    $paramName = $matches[1];
                    $constraint = $matches[2] ?? null;
                    
                    // If constraint is provided, use it
                    if ($constraint !== null)
                    {
                        // Common constraint shortcuts
                        return match($constraint)
                        {
                            'int', 'integer', 'num', 'number' => '(\d+)',
                            'alpha' => '([a-zA-Z]+)',
                            'alnum', 'alphanumeric' => '([a-zA-Z0-9]+)',
                            'uuid' => '([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})',
                            'slug' => '([a-z0-9]+(?:-[a-z0-9]+)*)',
                            default => '(' . $constraint . ')'
                        };
                    }
                    
                    // Default: match anything except forward slash
                    return '([^\/]+)';
                },
                $pattern
            );
            
            // Add start and end anchors
            $regex = '/^' . $pattern . '$/';
            Apcu::store($cacheKey, $regex);
            return self::$regexCache[$route] = $regex;
        }
    }