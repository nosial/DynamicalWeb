<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Classes\DebugPanel\ApcuTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\AppTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\CookiesTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\ConstantsTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\ExtensionsTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\IniTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\LocaleTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\OpcacheTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\PhpTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\ProfilerTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\RequestTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\ResponseTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\RoutesTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\SectionsTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\ServerTabBuilder;
    use DynamicalWeb\Classes\DebugPanel\SessionTabBuilder;
    use DynamicalWeb\Enums\PathConstants;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;
    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\WebSession;
    use Throwable;

    class DebugPanel
    {
        public static float $startTime;
        public static float $startMemory;
        public static array $executedFiles = [];
        public static array $executedSections = [];
        public static ?Request $currentRequest = null;
        public static ?Response $currentResponse = null;
        public static ?Route $currentRoute = null;

        /**
         * Starts the debug recording process
         *
         * @return void
         */
        public static function start(): void
        {
            self::$startTime        = microtime(true);
            self::$startMemory      = memory_get_usage(true);
            self::$executedFiles    = [];
            self::$executedSections = [];
            self::$currentRequest   = null;
            self::$currentResponse  = null;
            self::$currentRoute     = null;
        }

        /**
         * Tracks the execution of a file (PHP or PHTML) along with its execution time and memory usage
         *
         * @param string $filePath The full path of the executed file
         * @param string $type The type of file executed (e.g., 'module', 'phtml', 'included')
         * @param float|null $duration Optional duration of execution in seconds (if already measured), otherwise it will be measured internally
         * @return void
         */
        public static function trackFileExecution(string $filePath, string $type='module', ?float $duration=null): void
        {
            self::$executedFiles[] = [
                'path'     => $filePath,
                'type'     => $type,
                'time'     => microtime(true),
                'memory'   => memory_get_usage(true),
                'duration' => $duration,
            ];
        }

        /**
         * Records that a named section was rendered during this request.
         *
         * @param string $name     The section name
         * @param float  $duration Execution duration in seconds
         */
        public static function trackSectionExecution(string $name, float $duration): void
        {
            if (!isset(self::$executedSections[$name]))
            {
                self::$executedSections[$name] = ['count' => 0, 'totalDuration' => 0.0];
            }

            self::$executedSections[$name]['count']++;
            self::$executedSections[$name]['totalDuration'] += $duration;
        }

        /**
         * Injects the debug panel HTML into the response body if conditions are met
         *
         * @param Response $response The HTTP response object to modify
         * @param Request|null $request The current HTTP request object, if available
         * @param Route|null $route The matched route for the current request, if available
         * @return void
         */
        public static function inject(Response $response, ?Request $request, ?Route $route): void
        {
            if (!isset(self::$startTime) || !isset(self::$startMemory))
            {
                self::start();
            }

            if (!str_contains($response->getContentType(), 'text/html'))
            {
                return;
            }

            $body = $response->getBody();

            if (!str_contains($body, '</body>'))
            {
                return;
            }

            $panelHtml = self::generatePanel($request, $response, $route);
            $response->setBody(str_replace('</body>', $panelHtml . '</body>', $body));
        }

        /**
         * Generates the HTML content for the debug panel iframe, collecting all necessary metrics and information
         *
         * @param Request|null $request The current HTTP request object, if available
         * @param Response $response The current HTTP response object
         * @param Route|null $route The matched route for the current request, if available
         * @return string The HTML content for the debug panel iframe
         */
        private static function generatePanel(?Request $request, Response $response, ?Route $route): string
        {
            self::$currentRequest  = $request;
            self::$currentResponse = $response;
            self::$currentRoute    = $route;

            $includedFiles = get_included_files();

            $iframeVars = array_merge(
                self::collectMetrics(),
                self::collectRequestMetrics($request),
                self::collectRouteInfo($route),
                self::collectResponseMetrics($response),
                [
                    'dw_request_sections_html'    => RequestTabBuilder::build(),
                    'dw_response_sections_html'   => ResponseTabBuilder::build(),
                    'dw_cookies_sections_html'    => CookiesTabBuilder::build(),
                    'dw_cookie_count'            => count($_COOKIE),
                    'dw_app_sections_html'        => AppTabBuilder::build(),
                    'dw_php_sections_html'        => PhpTabBuilder::build(),
                    'dw_extensions_sections_html' => ExtensionsTabBuilder::build(),
                    'dw_opcache_sections_html'    => OpcacheTabBuilder::build(),
                    'dw_server_sections_html'     => ServerTabBuilder::build(),
                    'dw_constants_sections_html'  => ConstantsTabBuilder::build(),
                    'dw_session_sections_html'    => SessionTabBuilder::build(),
                    'dw_session_count'           => session_status() === PHP_SESSION_ACTIVE ? count($_SESSION) : -1,
                    'dw_routes_sections_html'     => RoutesTabBuilder::build(),
                    'dw_route_count'             => self::getRouteCount(),
                    'dw_sections_sections_html'   => SectionsTabBuilder::build(),
                    'dw_sections_count'          => self::getSectionCount(),
                    'dw_ini_sections_html'        => IniTabBuilder::build(),
                    'dw_apcu_sections_html'       => ApcuTabBuilder::build(),
                    'dw_has_apcu'                => Apcu::isExtensionAvailable(),
                    'dw_locale_sections_html'     => LocaleTabBuilder::build(),
                    'dw_has_locale'              => WebSession::getLocale() !== null,
                    'dw_locale_switcher_html'     => LocaleTabBuilder::buildLocaleSwitcherHtml($request),
                    'dw_error_log_html'           => self::buildErrorLogHtml(),
                    'dw_has_error_log'            => self::hasErrorLogAccess(),
                    'dw_executed_files_html'      => ProfilerTabBuilder::build(),
                    'dw_php_included_files_html'   => ProfilerTabBuilder::buildIncluded($includedFiles),
                    'dw_php_included_count'       => count($includedFiles),
                    'dw_response_body_size'       => self::formatBytes(strlen($response->getBody())),
                ]
            );

            $escapedContent = htmlspecialchars(self::generateIframeContent($iframeVars), ENT_QUOTES, 'UTF-8');

            return self::renderTemplate(
                PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'debug_panel.phtml'),
                ['escapedContent' => $escapedContent]
            );
        }

        /**
         * Collects performance metrics such as execution time, memory usage, and CPU time
         *
         * @return array An associative array containing formatted metrics for display in the debug panel
         */
        private static function collectMetrics(): array
        {
            $executionTime = microtime(true) - self::$startTime;
            $memoryUsage   = memory_get_usage(true) - self::$startMemory;

            $memLimitStr   = ini_get('memory_limit');
            $memLimitBytes = $memLimitStr === '-1' ? -1 : (static function(string $s): int {
                $unit  = strtolower(substr($s, -1));
                $value = (int) $s;
                return match($unit) {
                    'g' => $value * 1024 * 1024 * 1024,
                    'm' => $value * 1024 * 1024,
                    'k' => $value * 1024,
                    default => $value,
                };
            })($memLimitStr);

            $peakBytes = memory_get_peak_usage(true);
            $peakPct   = $memLimitBytes > 0 ? round($peakBytes / $memLimitBytes * 100, 1) : null;

            // CPU time consumed by this PHP process
            $cpuUser = $cpuSys = null;
            if (function_exists('getrusage'))
            {
                $ru      = getrusage();
                $cpuUser = ($ru['ru_utime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_utime.tv_usec'] ?? 0);
                $cpuSys  = ($ru['ru_stime.tv_sec'] ?? 0) * 1_000_000 + ($ru['ru_stime.tv_usec'] ?? 0);
            }

            return [
                'dw_formatted_time'    => self::formatTime($executionTime),
                'dw_formatted_memory'  => self::formatBytes($memoryUsage),
                'dw_formatted_peak'    => self::formatBytes($peakBytes),
                'dw_formatted_mem_limit'=> $memLimitBytes === -1 ? 'Unlimited' : self::formatBytes($memLimitBytes),
                'dw_peak_mem_pct'       => $peakPct !== null ? $peakPct . '%' : null,
                'dw_cpu_user'          => $cpuUser !== null ? self::formatTime($cpuUser / 1_000_000) : null,
                'dw_cpu_sys'           => $cpuSys  !== null ? self::formatTime($cpuSys  / 1_000_000) : null,
            ];
        }

        /**
         * Collects detailed information about the HTTP request, such as method, path, headers, and client info
         *
         * @param Request|null $request The current HTTP request object, if available
         * @return array An associative array containing request metrics for display in the debug panel
         */
        private static function collectRequestMetrics(?Request $request): array
        {
            // Build the debug API base URL so the iframe can poll the stats endpoint
            $debugStatsUrl = '';

            if ($request)
            {
                $scheme   = $request->isSecure() ? 'https' : 'http';
                $basePath = '';
                $instance = WebSession::getInstance();

                if ($instance !== null)
                {
                    $bp = $instance->getWebConfiguration()->getRouter()->getBasePath();
                    $basePath = rtrim($bp, '/');
                }

                $debugStatsUrl = $scheme . '://' . $request->getHost() . $basePath . '/dynaweb/debug/stats';
            }

            return [
                'dw_request_id'       => $request ? self::escape($request->getId()) : 'N/A',
                'dw_request_method'   => $request ? $request->getMethod()->value : 'N/A',
                'dw_request_path'     => $request ? $request->getPath() : 'N/A',
                'dw_request_host'     => $request ? $request->getHost() : 'N/A',
                'dw_client_ip'        => $request ? ($request->getClientIp() ?? 'Unknown') : 'Unknown',
                'dw_referer'         => self::escape($request ? ($request->getHeader('Referer') ?? 'None') : 'None'),
                'dw_is_secure'        => $request ? ($request->isSecure() ? 'Yes (HTTPS)' : 'No (HTTP)') : 'Unknown',
                'dw_http_version'     => $request ? $request->getHttpVersion() : 'N/A',
                'dw_detected_language'=> $request ? ($request->getDetectedLanguage() ?? 'N/A') : 'N/A',
                'dw_file_count'       => $request ? $request->getFileCount() : 0,
                'dw_total_file_size'   => $request ? self::formatBytes($request->getTotalFileSize()) : '0 B',
                'dw_headers_count'    => $request ? count($request->getHeaders()) : 0,
                'dw_query_count'      => $request ? count($request->getQueryParameters()) : 0,
                'dw_body_count'       => $request ? count($request->getBodyParameters()) : 0,
                'dw_path_count'       => $request ? count($request->getPathParameters()) : 0,
                'dw_cookies_count'    => $request ? count($request->getCookies()) : 0,
                'dw_debug_stats_url'   => $debugStatsUrl,
            ];
        }

        /**
         * Collects information about the matched route for the current request, such as path, module, locale, and allowed methods
         *
         * @param Route|null $route The matched route for the current request, if available
         * @return array An associative array containing route information for display in the debug panel
         */
        private static function collectRouteInfo(?Route $route): array
        {
            $routeAllowedMethods = 'N/A';

            if ($route)
            {
                $methods = array_map(static fn($m) => is_string($m) ? $m : $m->value, $route->getAllowedMethods());
                $routeAllowedMethods = $methods ? implode(', ', $methods) : 'Any';
            }

            return [
                'dw_escaped_module'      => self::escape($route ? $route->getModule() : 'N/A'),
                'dw_route_path'          => $route ? self::escape($route->getPath()) : 'N/A',
                'dw_route_locale_id'      => $route ? ($route->getLocaleId() ?? 'None') : 'N/A',
                'dw_route_allowed_methods'=> $routeAllowedMethods,
            ];
        }

        /**
         * Collects information about the HTTP response, such as status code, content type, charset, and headers count
         *
         * @param Response $response The HTTP response object
         * @return array An associative array containing response metrics for display in the debug panel
         */
        private static function collectResponseMetrics(Response $response): array
        {
            return [
                'dw_status_code'          => $response->getStatusCode()->value,
                'dw_status_text'          => self::escape($response->getStatusCode()->getMessage()),
                'dw_status_class'         => self::getStatusClass($response->getStatusCode()->value),
                'dw_escaped_content_type'  => self::escape($response->getContentType()),
                'dw_escaped_charset'      => self::escape($response->getCharset()),
                'dw_escaped_php_version'   => self::escape(PHP_VERSION),
                'dw_response_headers_count'=> count($response->getHeaders()),
                'dw_response_cookies_count'=> count($response->getCookies()),
                'dw_executed_files_count'  => count(self::$executedFiles),
            ];
        }

        /**
         * Returns the count of rendered sections during this request, safely handling any potential exceptions if the
         * WebSession or its sections are not available
         *
         * @return int The count of rendered sections, or 0 if not available
         */
        private static function getSectionCount(): int
        {
            try
            {
                $instance = WebSession::getInstance();
                return $instance ? count($instance->getSections()) : 0;
            }
            catch (Throwable)
            {
                return 0;
            }
        }

        /**
         * Checks if the error log file is configured and accessible for reading, which allows the debug panel to display recent errors
         *
         * @return bool True if the error log file is set and readable, false otherwise
         */
        private static function hasErrorLogAccess(): bool
        {
            $path = ini_get('error_log');
            return $path && file_exists($path) && is_readable($path);
        }

        /**
         * Builds the HTML content for the "Error Log" tab in the debug panel by reading the last 60 lines of the configured error log file
         *
         * @return string The HTML content representing the recent error log entries, or a message if the log is empty or inaccessible
         */
        private static function buildErrorLogHtml(): string
        {
            $path = ini_get('error_log');
            if (!$path || !file_exists($path) || !is_readable($path))
            {
                return '';
            }

            // Read last 60 lines efficiently without loading whole file
            $lines   = [];
            $handle  = fopen($path, 'r');
            if ($handle === false)
            {
                return '';
            }

            while (!feof($handle))
            {
                $line = fgets($handle);
                if ($line !== false)
                {
                    $lines[] = rtrim($line);
                    if (count($lines) > 60)
                    {
                        array_shift($lines);
                    }
                }
            }
            fclose($handle);

            if (empty($lines))
            {
                return '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">Error log is empty</div>';
            }

            $errorLevelColors = [
                'Fatal error'    => ['FATAL',  '#c0392b'],
                'Parse error'    => ['PARSE',  '#c0392b'],
                'Warning'        => ['WARN',   '#e67e22'],
                'Notice'         => ['NOTICE', '#7f8c8d'],
                'Deprecated'     => ['DEPR',   '#8e44ad'],
                'Stack trace'    => ['TRACE',  '#555555'],
                'Uncaught'       => ['THROW',  '#c0392b'],
            ];

            $html = '<div class="file-list">';
            foreach (array_reverse($lines) as $line)
            {
                if (trim($line) === '')
                {
                    continue;
                }

                $level = 'INFO';
                $color = '#2c4a6b';
                foreach ($errorLevelColors as $keyword => [$lbl, $clr])
                {
                    if (str_contains($line, $keyword))
                    {
                        $level = $lbl;
                        $color = $clr;
                        break;
                    }
                }

                $html .= '<div class="file-item">'
                       . '<span class="file-type" style="background:' . $color . ';min-width:44px;text-align:center;">' . $level . '</span>'
                       . '<span style="font-family:monospace;font-size:10px;color:#333;word-break:break-all;">' . self::escape($line) . '</span>'
                       . '</div>';
            }
            $html .= '</div>';

            return $html;
        }

        /**
         * Returns the count of defined routes in the web configuration, safely handling any potential exceptions if the
         * WebSession or its router are not available
         *
         * @return int The count of defined routes, or 0 if not available
         */
        private static function getRouteCount(): int
        {
            try
            {
                $instance = WebSession::getInstance();
                return $instance ? count($instance->getWebConfiguration()->getRouter()->getRoutes()) : 0;
            }
            catch (Throwable)
            {
                return 0;
            }
        }

        /**
         * Generates the HTML content for the debug panel iframe by storing all variables in WebSession,
         * rendering the template, then cleaning up the stored variables.
         *
         * @param array $vars An associative array of variables to make available to the template via WebSession
         * @return string The rendered HTML content for the debug panel iframe
         */
        private static function generateIframeContent(array $vars): string
        {
            foreach ($vars as $key => $value)
            {
                WebSession::set($key, $value);
            }

            $html = self::renderTemplate(
                PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'debug.phtml'),
                []
            );

            foreach (array_keys($vars) as $key)
            {
                WebSession::unset($key);
            }

            return $html;
        }

        /**
         * Renders a PHP template file with the given variables and returns the output as a string
         *
         * @param string $path The full path to the template file to render
         * @param array $vars An associative array of variables to extract and make available in the template scope
         * @return string The rendered content of the template
         */
        private static function renderTemplate(string $path, array $vars): string
        {
            $render = static function(string $templatePath, array $templateVars): string
            {
                extract($templateVars);
                ob_start();
                include $templatePath;
                return ob_get_clean();
            };

            return $render($path, $vars);
        }

        /**
         * Formats a time duration in seconds into a human-readable string with appropriate units (μs, ms, s)
         *
         * @param float $seconds The time duration in seconds
         * @return string The formatted time string with units
         */
        private static function formatTime(float $seconds): string
        {
            if ($seconds < 0.001)
            {
                return number_format($seconds * 1_000_000, 2) . ' μs';
            }

            if ($seconds < 1)
            {
                return number_format($seconds * 1_000, 2) . ' ms';
            }

            return number_format($seconds, 3) . ' s';
        }

        /**
         * Formats a byte count into a human-readable string with appropriate units (B, KB, MB, GB, TB)
         *
         * @param int $bytes The number of bytes
         * @return string The formatted byte size string with units
         */
        private static function formatBytes(int $bytes): string
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow   = min((int) floor($bytes ? log($bytes) / log(1024) : 0), count($units) - 1);

            return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
        }

        /**
         * Determines the CSS class for the response status code to visually indicate success, warning, or error
         *
         * @param int $statusCode The HTTP status code of the response
         * @return string The CSS class name corresponding to the status code category
         */
        private static function getStatusClass(int $statusCode): string
        {
            if ($statusCode >= 200 && $statusCode < 300) return 'dw-status-success';
            if ($statusCode >= 300 && $statusCode < 400) return 'dw-status-warning';

            return 'dw-status-error';
        }

        /**
         * Escapes a string for safe output in HTML contexts to prevent XSS vulnerabilities
         *
         * @param string $str The input string to escape
         * @return string The escaped string safe for HTML output
         */
        private static function escape(string $str): string
        {
            return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
