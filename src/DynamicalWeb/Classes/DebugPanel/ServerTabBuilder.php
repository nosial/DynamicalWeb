<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class ServerTabBuilder
    {
        public static function build(): string
        {
            $envVars = [];
            foreach ($_ENV as $k => $v)
            {
                $envVars[Shared::escape((string) $k)] = Shared::escape(is_array($v) ? json_encode($v) : (string) $v);
            }

            $uptime = 'N/A';
            if (is_readable('/proc/uptime'))
            {
                $raw = @file_get_contents('/proc/uptime');
                if ($raw !== false)
                {
                    $secs = (int) explode(' ', $raw)[0];
                    $d = intdiv($secs, 86400);
                    $h = intdiv($secs % 86400, 3600);
                    $m = intdiv($secs % 3600, 60);
                    $s = $secs % 60;
                    $uptime = ($d > 0 ? $d . 'd ' : '') . sprintf('%02d:%02d:%02d', $h, $m, $s);
                }
            }

            $cpuCores = 'N/A';
            if (is_readable('/proc/cpuinfo'))
            {
                $cpuCores = (string) substr_count(@file_get_contents('/proc/cpuinfo') ?: '', 'processor');
            }
            elseif (function_exists('shell_exec'))
            {
                $nproc = trim((string) @shell_exec('nproc 2>/dev/null'));
                if ($nproc !== '')
                {
                    $cpuCores = $nproc;
                }
            }

            $html =
                Shared::buildSection('Server Environment', Shared::buildParametersHtml([
                    'Hostname'          => Shared::escape(gethostname() ?: 'N/A'),
                    'Server Software'   => Shared::escape($_SERVER['SERVER_SOFTWARE']   ?? 'N/A'),
                    'Server Name'       => Shared::escape($_SERVER['SERVER_NAME']       ?? 'N/A'),
                    'Server Port'       => Shared::escape($_SERVER['SERVER_PORT']       ?? 'N/A'),
                    'Server Admin'      => Shared::escape($_SERVER['SERVER_ADMIN']      ?? 'N/A'),
                    'Server Signature'  => Shared::escape($_SERVER['SERVER_SIGNATURE']  ?? 'N/A'),
                    'Protocol'          => Shared::escape($_SERVER['SERVER_PROTOCOL']   ?? 'N/A'),
                    'Document Root'     => Shared::escape($_SERVER['DOCUMENT_ROOT']     ?? 'N/A'),
                    'Script Filename'   => Shared::escape($_SERVER['SCRIPT_FILENAME']   ?? 'N/A'),
                    'Script Name'       => Shared::escape($_SERVER['SCRIPT_NAME']       ?? 'N/A'),
                    'Gateway Interface' => Shared::escape($_SERVER['GATEWAY_INTERFACE'] ?? 'N/A'),
                    'OS'                => Shared::escape(PHP_OS),
                    'Architecture'      => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
                    'CPU Cores'         => $cpuCores,
                    'System Uptime'     => $uptime,
                    'PHP Process PID'   => (string) getmypid(),
                ])) .
                (!empty($envVars) ? Shared::buildSection('Environment Variables (' . count($envVars) . ')', Shared::buildParametersHtml($envVars)) : '') .
                self::buildServerVarsSection();

            if (is_readable('/proc/meminfo'))
            {
                $raw = @file_get_contents('/proc/meminfo');
                if ($raw !== false)
                {
                    $memInfo = [];
                    foreach (explode("\n", $raw) as $line)
                    {
                        if (preg_match('/^(\w+):\s+(\d+)\s+kB/', $line, $m))
                        {
                            $memInfo[$m[1]] = (int) $m[2];
                        }
                    }
                    $wanted  = ['MemTotal', 'MemFree', 'MemAvailable', 'Buffers', 'Cached', 'SwapTotal', 'SwapFree'];
                    $memData = [];
                    foreach ($wanted as $key)
                    {
                        if (isset($memInfo[$key]))
                        {
                            $memData[$key] = Shared::formatBytes($memInfo[$key] * 1024);
                        }
                    }
                    if (isset($memInfo['MemTotal'], $memInfo['MemAvailable']))
                    {
                        $memData['Used'] = Shared::formatBytes(($memInfo['MemTotal'] - $memInfo['MemAvailable']) * 1024);
                    }
                    if (!empty($memData))
                    {
                        $html .= Shared::buildSection('System Memory', Shared::buildParametersHtml($memData));
                    }
                }
            }

            if (is_readable('/proc/loadavg'))
            {
                $raw = @file_get_contents('/proc/loadavg');
                if ($raw !== false)
                {
                    $parts = explode(' ', trim($raw));
                    if (count($parts) >= 5)
                    {
                        $html .= Shared::buildSection('System Load Average', Shared::buildParametersHtml([
                            '1 min'         => $parts[0],
                            '5 min'         => $parts[1],
                            '15 min'        => $parts[2],
                            'Running/Total' => $parts[3],
                            'Last PID'      => $parts[4],
                        ]));
                    }
                }
            }

            if (function_exists('disk_total_space') && function_exists('disk_free_space'))
            {
                $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
                $total   = @disk_total_space($docRoot);
                $free    = @disk_free_space($docRoot);
                if ($total !== false && $free !== false && $total > 0)
                {
                    $used    = $total - $free;
                    $usedPct = round($used / $total * 100, 1);
                    $html   .= Shared::buildSection('Disk Usage', Shared::buildParametersHtml([
                        'Total'  => Shared::formatBytes((int) $total),
                        'Free'   => Shared::formatBytes((int) $free),
                        'Used'   => Shared::formatBytes((int) $used),
                        'Used %' => $usedPct . '%',
                    ]));
                }
            }

            return $html;
        }

        public static function buildServerVarsSection(): string
        {
            $alreadyShown = [
                'SERVER_SOFTWARE', 'SERVER_NAME', 'SERVER_ADMIN', 'SERVER_SIGNATURE',
                'SERVER_PROTOCOL', 'DOCUMENT_ROOT', 'SCRIPT_FILENAME', 'SCRIPT_NAME',
                'GATEWAY_INTERFACE',
            ];

            $sensitive = ['password', 'passwd', 'secret', 'token', 'key', 'auth', 'credential', 'private', 'api_key'];

            $data = [];
            foreach ($_SERVER as $k => $v)
            {
                if (in_array($k, $alreadyShown, true))
                {
                    continue;
                }

                $lower       = strtolower((string) $k);
                $isSensitive = false;
                foreach ($sensitive as $pattern)
                {
                    if (str_contains($lower, $pattern))
                    {
                        $isSensitive = true;
                        break;
                    }
                }

                $display = $isSensitive ? '[REDACTED]' : (is_array($v) ? json_encode($v) : (string) $v);

                if ($display === '')
                {
                    continue;
                }

                $data[Shared::escape((string) $k)] = Shared::escape($display);
            }

            if (empty($data))
            {
                return '';
            }

            ksort($data);
            return Shared::buildSection('$_SERVER (' . count($data) . ')', Shared::buildParametersHtml($data));
        }
    }
