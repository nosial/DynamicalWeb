<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Classes\Apcu;
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

    class DebugPanel
    {
        public static float $startTime;
        public static float $startMemory;
        private static array $executedFiles = [];
        private static array $executedSections = [];

        /**
         * Starts the debug recording process
         *
         * @return void
         */
        public static function start(): void
        {
            self::$startTime      = microtime(true);
            self::$startMemory    = memory_get_usage(true);
            self::$executedFiles    = [];
            self::$executedSections = [];
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

        private static function generatePanel(?Request $request, Response $response, ?Route $route): string
        {
            $includedFiles = get_included_files();

            $iframeVars = array_merge(
                self::collectMetrics(),
                self::collectRequestMetrics($request),
                self::collectRouteInfo($route),
                self::collectResponseMetrics($response),
                [
                    'requestSectionsHtml'    => RequestTabBuilder::build($request),
                    'responseSectionsHtml'   => ResponseTabBuilder::build($response),
                    'cookiesSectionsHtml'    => CookiesTabBuilder::build($response),
                    'cookieCount'            => count($_COOKIE),
                    'appSectionsHtml'        => AppTabBuilder::build(),
                    'phpSectionsHtml'        => PhpTabBuilder::build(),
                    'extensionsSectionsHtml' => ExtensionsTabBuilder::build(),
                    'opcacheSectionsHtml'    => OpcacheTabBuilder::build(),
                    'serverSectionsHtml'     => ServerTabBuilder::build(),
                    'constantsSectionsHtml'  => ConstantsTabBuilder::build(),
                    'sessionSectionsHtml'    => SessionTabBuilder::build(),
                    'sessionCount'           => session_status() === PHP_SESSION_ACTIVE ? count($_SESSION) : -1,
                    'routesSectionsHtml'     => RoutesTabBuilder::build($route),
                    'routeCount'             => self::getRouteCount(),
                    'sectionsSectionsHtml'   => SectionsTabBuilder::build(self::$executedSections),
                    'sectionsCount'          => self::getSectionCount(),
                    'iniSectionsHtml'        => IniTabBuilder::build(),
                    'apcuSectionsHtml'       => ApcuTabBuilder::build(),
                    'hasApcu'                => Apcu::isExtensionAvailable(),
                    'localeSectionsHtml'     => LocaleTabBuilder::build(),
                    'hasLocale'              => WebSession::getLocale() !== null,
                    'localeSwitcherHtml'     => LocaleTabBuilder::buildLocaleSwitcherHtml($request),
                    'errorLogHtml'           => self::buildErrorLogHtml(),
                    'hasErrorLog'            => self::hasErrorLogAccess(),
                    'executedFilesHtml'      => ProfilerTabBuilder::build(self::$executedFiles),
                    'phpIncludedFilesHtml'   => ProfilerTabBuilder::buildIncluded($includedFiles),
                    'phpIncludedCount'       => count($includedFiles),
                    'responseBodySize'       => self::formatBytes(strlen($response->getBody())),
                ]
            );

            $escapedContent = htmlspecialchars(self::generateIframeContent($iframeVars), ENT_QUOTES, 'UTF-8');

            return self::renderTemplate(
                PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'debug_panel.phtml'),
                ['escapedContent' => $escapedContent]
            );
        }

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
                'formattedTime'    => self::formatTime($executionTime),
                'formattedMemory'  => self::formatBytes($memoryUsage),
                'formattedPeak'    => self::formatBytes($peakBytes),
                'formattedMemLimit'=> $memLimitBytes === -1 ? 'Unlimited' : self::formatBytes($memLimitBytes),
                'peakMemPct'       => $peakPct !== null ? $peakPct . '%' : null,
                'cpuUser'          => $cpuUser !== null ? self::formatTime($cpuUser / 1_000_000) : null,
                'cpuSys'           => $cpuSys  !== null ? self::formatTime($cpuSys  / 1_000_000) : null,
            ];
        }

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
                'requestId'       => $request ? self::escape($request->getId()) : 'N/A',
                'requestMethod'   => $request ? $request->getMethod()->value : 'N/A',
                'requestPath'     => $request ? $request->getPath() : 'N/A',
                'requestHost'     => $request ? $request->getHost() : 'N/A',
                'clientIp'        => $request ? ($request->getClientIp() ?? 'Unknown') : 'Unknown',
                'referer'         => self::escape($request ? ($request->getHeader('Referer') ?? 'None') : 'None'),
                'isSecure'        => $request ? ($request->isSecure() ? 'Yes (HTTPS)' : 'No (HTTP)') : 'Unknown',
                'httpVersion'     => $request ? $request->getHttpVersion() : 'N/A',
                'detectedLanguage'=> $request ? ($request->getDetectedLanguage() ?? 'N/A') : 'N/A',
                'fileCount'       => $request ? $request->getFileCount() : 0,
                'totalFileSize'   => $request ? self::formatBytes($request->getTotalFileSize()) : '0 B',
                'headersCount'    => $request ? count($request->getHeaders()) : 0,
                'queryCount'      => $request ? count($request->getQueryParameters()) : 0,
                'bodyCount'       => $request ? count($request->getBodyParameters()) : 0,
                'pathCount'       => $request ? count($request->getPathParameters()) : 0,
                'cookiesCount'    => $request ? count($request->getCookies()) : 0,
                'debugStatsUrl'   => $debugStatsUrl,
            ];
        }

        private static function collectRouteInfo(?Route $route): array
        {
            $routeAllowedMethods = 'N/A';
            if ($route)
            {
                $methods = array_map(static fn($m) => is_string($m) ? $m : $m->value, $route->getAllowedMethods());
                $routeAllowedMethods = $methods ? implode(', ', $methods) : 'Any';
            }

            return [
                'escapedModule'      => self::escape($route ? $route->getModule() : 'N/A'),
                'routePath'          => $route ? self::escape($route->getPath()) : 'N/A',
                'routeLocaleId'      => $route ? ($route->getLocaleId() ?? 'None') : 'N/A',
                'routeAllowedMethods'=> $routeAllowedMethods,
            ];
        }

        private static function collectResponseMetrics(Response $response): array
        {
            $statusCode = $response->getStatusCode()->value;

            return [
                'statusCode'          => $statusCode,
                'statusText'          => self::escape($response->getStatusCode()->getMessage()),
                'statusClass'         => self::getStatusClass($statusCode),
                'escapedContentType'  => self::escape($response->getContentType()),
                'escapedCharset'      => self::escape($response->getCharset()),
                'escapedPhpVersion'   => self::escape(PHP_VERSION),
                'responseHeadersCount'=> count($response->getHeaders()),
                'responseCookiesCount'=> count($response->getCookies()),
                'executedFilesCount'  => count(self::$executedFiles),
            ];
        }

        private static function getSectionCount(): int
        {
            try
            {
                $instance = WebSession::getInstance();
                return $instance ? count($instance->getSections()) : 0;
            }
            catch (\Throwable)
            {
                return 0;
            }
        }
        private static function hasErrorLogAccess(): bool
        {
            $path = ini_get('error_log');
            return $path && file_exists($path) && is_readable($path);
        }

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

        private static function getRouteCount(): int
        {
            try
            {
                $instance = WebSession::getInstance();
                return $instance ? count($instance->getWebConfiguration()->getRouter()->getRoutes()) : 0;
            }
            catch (\Throwable)
            {
                return 0;
            }
        }
        private static function generateIframeContent(array $vars): string
        {
            return self::renderTemplate(PathResolver::buildNccPath(PathConstants::DYNAMICAL_WEB->value, PathConstants::DYNAMICAL_PAGES->value, 'debug.phtml'), $vars);
        }

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

        // -------------------------------------------------------------------------
        // Formatters & utilities
        // -------------------------------------------------------------------------

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

        private static function formatBytes(int $bytes): string
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow   = min((int) floor($bytes ? log($bytes) / log(1024) : 0), count($units) - 1);

            return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
        }

        private static function getStatusClass(int $statusCode): string
        {
            if ($statusCode >= 200 && $statusCode < 300) return 'dw-status-success';
            if ($statusCode >= 300 && $statusCode < 400) return 'dw-status-warning';
            return 'dw-status-error';
        }

        private static function escape(string $str): string
        {
            return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
