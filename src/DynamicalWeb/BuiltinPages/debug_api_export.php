<?php

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\WebSession;

    // Only available when debug panel is enabled
    $instance = WebSession::getInstance();
    if ($instance === null || !$instance->getWebConfiguration()->getApplication()->isDebugPanelEnabled())
    {
        WebSession::getResponse()->setStatusCode(ResponseCode::NOT_FOUND);
        WebSession::getResponse()->setContentType(MimeType::TEXT);
        WebSession::getResponse()->setBody('Not Found');
        return;
    }

    $request  = WebSession::getRequest();
    $response = WebSession::getResponse();

    $bytesToStr = static function(int $bytes): string
    {
        if ($bytes < 1024)      return $bytes . ' B';
        if ($bytes < 1048576)   return round($bytes / 1024, 1)    . ' KB';
        if ($bytes < 1073741824)return round($bytes / 1048576, 1) . ' MB';
        return round($bytes / 1073741824, 2) . ' GB';
    };

    $memLimit = static function(string $limit): int
    {
        if ($limit === '-1') return -1;
        $unit  = strtolower(substr($limit, -1));
        $value = (int) $limit;
        return match($unit)
        {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    };

    $export = [
        'meta' => [
            'generated_at'    => date(DATE_ATOM),
            'generated_at_ms' => (int) round(microtime(true) * 1000),
            'framework'       => 'DynamicalWeb',
            'php_version'     => PHP_VERSION,
        ],
    ];

    // Request
    if ($request !== null)
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $value)
        {
            $headers[(string) $name] = (string) $value;
        }
        $export['request'] = [
            'id'                => $request->getId(),
            'method'            => $request->getMethod()->value,
            'path'              => $request->getPath(),
            'host'              => $request->getHost(),
            'http_version'      => $request->getHttpVersion(),
            'scheme'            => $request->isSecure() ? 'https' : 'http',
            'client_ip'         => $request->getClientIp(),
            'referer'           => $request->getHeader('Referer'),
            'detected_language' => $request->getDetectedLanguage(),
            'headers'           => $headers,
            'query_parameters'  => $request->getQueryParameters(),
            'body_parameters'   => $request->getBodyParameters(),
            'form_parameters'   => $request->getFormParameters(),
            'path_parameters'   => $request->getPathParameters(),
            'cookies'           => $request->getCookies(),
            'file_count'        => $request->getFileCount(),
            'total_file_size'   => $request->getTotalFileSize(),
        ];
    }
    else
    {
        $export['request'] = null;
    }

    // Response
    $statusCode = $response->getStatusCode()->value;
    $respHeaders = [];
    foreach ($response->getHeaders() as $name => $value)
    {
        $respHeaders[(string) $name] = (string) $value;
    }
    $export['response'] = [
        'status_code'  => $statusCode,
        'content_type' => $response->getContentType(),
        'charset'      => $response->getCharset(),
        'body_size'    => strlen($response->getBody()),
        'headers'      => $respHeaders,
    ];

    // Runtime metrics
    $memLimitBytes = $memLimit(ini_get('memory_limit'));
    $memUsage      = memory_get_usage(true);
    $memPeak       = memory_get_peak_usage(true);
    $export['runtime'] = [
        'memory_usage'     => $memUsage,
        'memory_usage_str' => $bytesToStr($memUsage),
        'memory_peak'      => $memPeak,
        'memory_peak_str'  => $bytesToStr($memPeak),
        'memory_limit'     => $memLimitBytes,
        'memory_limit_str' => ini_get('memory_limit'),
        'memory_usage_pct' => $memLimitBytes > 0 ? round($memUsage / $memLimitBytes * 100, 1) : null,
    ];

    // Server & PHP
    $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
    $export['server'] = [
        'os'              => PHP_OS,
        'os_family'       => PHP_OS_FAMILY,
        'architecture'    => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
        'sapi'            => php_sapi_name() ?: null,
        'timezone'        => ini_get('date.timezone') ?: date_default_timezone_get(),
        'load_avg'        => $loadAvg ? ['1min' => $loadAvg[0], '5min' => $loadAvg[1], '15min' => $loadAvg[2]] : null,
        'disk_free'       => ($df = @disk_free_space('/')) !== false ? (int) $df : null,
        'disk_total'      => ($dt = @disk_total_space('/')) !== false ? (int) $dt : null,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? null,
        'document_root'   => $_SERVER['DOCUMENT_ROOT']   ?? null,
    ];

    $export['php'] = [
        'version'          => PHP_VERSION,
        'version_id'       => PHP_VERSION_ID,
        'ini_file'         => php_ini_loaded_file() ?: null,
        'memory_limit'     => ini_get('memory_limit'),
        'max_exec_time'    => ini_get('max_execution_time'),
        'display_errors'   => (bool) ini_get('display_errors'),
        'error_reporting'  => (int) ini_get('error_reporting'),
        'upload_max'       => ini_get('upload_max_filesize'),
        'post_max'         => ini_get('post_max_size'),
        'extensions'       => get_loaded_extensions(),
        'included_files'   => get_included_files(),
        'user_constants'   => get_defined_constants(true)['user'] ?? [],
    ];

    // OPcache
    if (function_exists('opcache_get_status'))
    {
        $opcStatus = @opcache_get_status(false);
        if ($opcStatus !== false)
        {
            $export['opcache'] = [
                'enabled'        => (bool) ($opcStatus['opcache_enabled'] ?? false),
                'hit_rate'       => round((float) ($opcStatus['opcache_statistics']['opcache_hit_rate'] ?? 0), 2),
                'used_memory'    => (int) ($opcStatus['memory_usage']['used_memory']  ?? 0),
                'free_memory'    => (int) ($opcStatus['memory_usage']['free_memory']  ?? 0),
                'cached_scripts' => (int) ($opcStatus['opcache_statistics']['num_cached_scripts'] ?? 0),
            ];
        }
    }

    // Session
    $export['session'] = [
        'status' => match(session_status())
        {
            PHP_SESSION_DISABLED => 'disabled',
            PHP_SESSION_NONE     => 'not_started',
            PHP_SESSION_ACTIVE   => 'active',
            default              => 'unknown',
        },
    ];

    if (session_status() === PHP_SESSION_ACTIVE)
    {
        $export['session']['id']   = session_id();
        $export['session']['data'] = $_SESSION ?? [];
        $export['session']['cookie_params'] = session_get_cookie_params();
    }

    // Routes
    try
    {
        $webInstance = WebSession::getInstance();
        $routes      = $webInstance ? $webInstance->getWebConfiguration()->getRouter()->getRoutes() : [];
        $routeList   = [];
        foreach ($routes as $route)
        {
            $methods = array_map(static fn($m) => is_string($m) ? $m : $m->value, $route->getAllowedMethods());
            $routeList[] = [
                'path'          => $route->getPath(),
                'methods'       => $methods,
                'module'        => $route->getModule(),
                'locale_id'     => $route->getLocaleId(),
            ];
        }
        $export['routes'] = $routeList;
    }
    catch (Throwable)
    {
        $export['routes'] = [];
    }

    // Environment variables (sanitized — skip anything that looks like a secret)
    $sensitivePatterns = ['secret', 'password', 'passwd', 'token', 'key', 'auth', 'credential', 'private', 'api_key'];
    $envExport = [];
    foreach ($_ENV as $k => $v)
    {
        $lower = strtolower((string) $k);
        $isSensitive = false;
        foreach ($sensitivePatterns as $pattern)
        {
            if (str_contains($lower, $pattern))
            {
                $isSensitive = true;
                break;
            }
        }
        $envExport[(string) $k] = $isSensitive ? '[REDACTED]' : (is_array($v) ? $v : (string) $v);
    }
    $export['env'] = $envExport;

    // Application config summary
    try
    {
        $appConfig = $instance->getWebConfiguration()->getApplication();
        $export['application'] = [
            'name'               => $appConfig->getName(),
            'debug_panel'        => $appConfig->isDebugPanelEnabled(),
            'error_reporting'    => $appConfig->errorReportingEnabled(),
            'xss_protection'     => $appConfig->getXssLevel()->value ?? $appConfig->getXssLevel(),
        ];
    }
    catch (Throwable)
    {
        $export['application'] = null;
    }

    WebSession::getResponse()->setContentType(MimeType::JSON);
    WebSession::getResponse()->setStatusCode(ResponseCode::OK);
    WebSession::getResponse()->setHeader('Content-Disposition', 'attachment; filename="dwdebug-' . date('Ymd-His') . '.json"');
    WebSession::getResponse()->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    WebSession::getResponse()->setBody(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
