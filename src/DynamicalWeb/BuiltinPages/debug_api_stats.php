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

    // Memory
    $memUsage     = memory_get_usage(true);
    $memPeak      = memory_get_peak_usage(true);
    $memLimitStr  = ini_get('memory_limit');

    $memLimitBytes = (static function(string $limit): int
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
    })($memLimitStr);

    // CPU load averages (Unix only)
    $loadAvg = function_exists('sys_getloadavg') ? sys_getloadavg() : [0.0, 0.0, 0.0];

    // Disk space for web root
    $diskFree  = @disk_free_space('/');
    $diskTotal = @disk_total_space('/');

    // OPcache
    $opcache = null;
    if (function_exists('opcache_get_status'))
    {
        $status = @opcache_get_status(false);
        if ($status !== false)
        {
            $usedMem   = (int) ($status['memory_usage']['used_memory']           ?? 0);
            $freeMem   = (int) ($status['memory_usage']['free_memory']           ?? 0);
            $wastedMem = (int) ($status['memory_usage']['wasted_memory']         ?? 0);
            $totalMem  = $usedMem + $freeMem + $wastedMem;
            $opcache = [
                'enabled'        => (bool) ($status['opcache_enabled'] ?? false),
                'hit_rate'       => round((float) ($status['opcache_statistics']['opcache_hit_rate'] ?? 0), 2),
                'used_memory'    => $usedMem,
                'free_memory'    => $freeMem,
                'wasted_memory'  => $wastedMem,
                'total_memory'   => $totalMem,
                'mem_pct'        => $totalMem > 0 ? round($usedMem / $totalMem * 100, 1) : 0.0,
                'wasted_pct'     => $totalMem > 0 ? round($wastedMem / $totalMem * 100, 1) : 0.0,
                'cached_scripts' => (int) ($status['opcache_statistics']['num_cached_scripts'] ?? 0),
            ];
        }
    }

    // APCu
    $apcu = null;
    if (function_exists('apcu_enabled') && apcu_enabled())
    {
        $apcuInfo = @apcu_cache_info(true);
        $apcuSma  = @apcu_sma_info(true);
        if ($apcuInfo !== false && $apcuSma !== false)
        {
            $apcuHits    = (int) ($apcuInfo['num_hits']   ?? 0);
            $apcuMisses  = (int) ($apcuInfo['num_misses'] ?? 0);
            $apcuRequests = $apcuHits + $apcuMisses;
            $apcuMemTotal = (int) (($apcuSma['num_seg'] ?? 1) * ($apcuSma['seg_size'] ?? 0));
            $apcuMemFree  = (int) ($apcuSma['avail_mem'] ?? 0);
            $apcuMemUsed  = $apcuMemTotal - $apcuMemFree;
            $apcu = [
                'enabled'      => true,
                'hits'         => $apcuHits,
                'misses'       => $apcuMisses,
                'hit_rate'     => $apcuRequests > 0 ? round($apcuHits / $apcuRequests * 100, 1) : 0.0,
                'entries'      => (int) ($apcuInfo['num_entries'] ?? 0),
                'used_memory'  => $apcuMemUsed,
                'free_memory'  => $apcuMemFree,
                'total_memory' => $apcuMemTotal,
                'mem_pct'      => $apcuMemTotal > 0 ? round($apcuMemUsed / $apcuMemTotal * 100, 1) : 0.0,
            ];
        }
    }

    $stats = [
        'timestamp' => (int) round(microtime(true) * 1000),
        'memory' => [
            'usage'     => $memUsage,
            'peak'      => $memPeak,
            'limit'     => $memLimitBytes,
            'usage_pct' => $memLimitBytes > 0 ? round($memUsage / $memLimitBytes * 100, 1) : 0.0,
            'peak_pct'  => $memLimitBytes > 0 ? round($memPeak  / $memLimitBytes * 100, 1) : 0.0,
        ],
        'load' => [
            'avg1'  => round((float) $loadAvg[0], 2),
            'avg5'  => round((float) $loadAvg[1], 2),
            'avg15' => round((float) $loadAvg[2], 2),
        ],
        'disk' => [
            'free'     => $diskFree  !== false ? (int) $diskFree  : null,
            'total'    => $diskTotal !== false ? (int) $diskTotal : null,
            'used_pct' => ($diskFree !== false && $diskTotal !== false && $diskTotal > 0)
                ? round(($diskTotal - $diskFree) / $diskTotal * 100, 1)
                : null,
        ],
        'opcache' => $opcache,
        'apcu'    => $apcu,
        'php' => [
            'included_files' => count(get_included_files()),
        ],
    ];

    WebSession::getResponse()->setContentType(MimeType::JSON);
    WebSession::getResponse()->setStatusCode(ResponseCode::OK);
    WebSession::getResponse()->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    WebSession::getResponse()->setBody(json_encode($stats, JSON_UNESCAPED_SLASHES));
