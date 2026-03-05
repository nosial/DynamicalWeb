<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;
    use DynamicalWeb\Classes\DebugPanel as DebugPanelClass;
    use DynamicalWeb\WebSession;
    use Throwable;

    class AppTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $appName = $appXssLevel = $appErrReport = 'N/A';
            $routerBaseUrl = $routerBasePath = $defaultLocale = 'N/A';
            $preRequest = $postRequest = $routeCount = $availableLocales = 'N/A';
            $webRoot = $webResources = $sectionNames = $responseHandlers = 'N/A';
            $debugPanel = 'N/A';

            try
            {
                $instance = WebSession::getInstance();
                if ($instance !== null)
                {
                    $appCfg           = $instance->getWebConfiguration()->getApplication();
                    $routerCfg        = $instance->getWebConfiguration()->getRouter();
                    $appName          = self::escape($appCfg->getName());
                    $appXssLevel      = self::escape($appCfg->getXssLevel()->name);
                    $appErrReport     = $appCfg->errorReportingEnabled() ? 'Enabled' : 'Disabled';
                    $routerBaseUrl    = self::escape($routerCfg->getBaseUrl());
                    $routerBasePath   = self::escape($routerCfg->getBasePath());
                    $defaultLocale    = self::escape($appCfg->getDefaultLocale() ?? 'None');
                    $pre              = $appCfg->getPreRequest();
                    $post             = $appCfg->getPostRequest();
                    $preRequest       = $pre  ? implode(', ', $pre)  : 'None';
                    $postRequest      = $post ? implode(', ', $post) : 'None';
                    $routeCount       = (string) count($routerCfg->getRoutes());
                    $localeCodes      = $instance->getAvailableLocaleCodes();
                    $availableLocales = $localeCodes ? implode(', ', $localeCodes) : 'None';
                    $webRoot          = self::escape($instance->getWebRootPath());
                    $webResources     = self::escape($instance->getWebResourcesPath());

                    $sections     = $instance->getSections();
                    $sectionNames = $sections ? implode(', ', array_keys($sections)) : 'None';

                    $handlers = $routerCfg->getResponseHandlers();
                    if (!empty($handlers))
                    {
                        $parts = [];
                        foreach ($handlers as $code => $module)
                        {
                            $parts[] = $code . ' → ' . $module;
                        }
                        $responseHandlers = implode(', ', $parts);
                    }
                    else
                    {
                        $responseHandlers = 'None';
                    }

                    $debugPanel = $appCfg->isDebugPanelEnabled() ? 'Enabled' : 'Disabled';
                }
            }
            catch (Throwable) {}

            $elapsed     = microtime(true) - DebugPanelClass::$startTime;
            $memCurrent  = memory_get_usage(true);
            $memDelta    = $memCurrent - (int) DebugPanelClass::$startMemory;
            $memDeltaStr = ($memDelta >= 0 ? '+' : '-') . self::formatBytes(abs($memDelta));

            return self::buildSection('Application', self::buildParametersHtml([
                'Name'              => $appName,
                'Error Reporting'   => $appErrReport,
                'XSS Protection'    => $appXssLevel,
                'Debug Panel'       => $debugPanel,
                'Base URL'          => $routerBaseUrl,
                'Base Path'         => $routerBasePath,
                'Web Root'          => $webRoot,
                'Web Resources'     => $webResources,
                'Default Locale'    => $defaultLocale,
                'Available Locales' => $availableLocales,
                'Route Count'       => $routeCount,
                'Sections'          => $sectionNames,
                'Response Handlers' => $responseHandlers,
                'Pre-Request'       => $preRequest,
                'Post-Request'      => $postRequest,
            ])) . self::buildSection('Runtime', self::buildParametersHtml([
                'Elapsed Time'     => self::formatTime($elapsed),
                'Memory at Start'  => self::formatBytes((int) DebugPanelClass::$startMemory),
                'Memory Current'   => self::formatBytes($memCurrent),
                'Memory Peak'      => self::formatBytes(memory_get_peak_usage(true)),
                'Memory Delta'     => $memDeltaStr,
                'PHP Request Time' => isset($_SERVER['REQUEST_TIME_FLOAT'])
                    ? self::formatTime(microtime(true) - (float) $_SERVER['REQUEST_TIME_FLOAT'])
                    : 'N/A',
            ]));
        }
    }
