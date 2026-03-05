<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;

    class ServerTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $envVars = [];
            foreach ($_ENV as $k => $v)
            {
                $envVars[self::escape((string) $k)] = self::escape(is_array($v) ? json_encode($v) : (string) $v);
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
                self::buildSection('Server Environment', self::buildParametersHtml([
                    'Hostname'          => self::escape(gethostname() ?: 'N/A'),
                    'Server Software'   => self::escape($_SERVER['SERVER_SOFTWARE']   ?? 'N/A'),
                    'Server Name'       => self::escape($_SERVER['SERVER_NAME']       ?? 'N/A'),
                    'Server Port'       => self::escape($_SERVER['SERVER_PORT']       ?? 'N/A'),
                    'Server Admin'      => self::escape($_SERVER['SERVER_ADMIN']      ?? 'N/A'),
                    'Server Signature'  => self::escape($_SERVER['SERVER_SIGNATURE']  ?? 'N/A'),
                    'Protocol'          => self::escape($_SERVER['SERVER_PROTOCOL']   ?? 'N/A'),
                    'Document Root'     => self::escape($_SERVER['DOCUMENT_ROOT']     ?? 'N/A'),
                    'Script Filename'   => self::escape($_SERVER['SCRIPT_FILENAME']   ?? 'N/A'),
                    'Script Name'       => self::escape($_SERVER['SCRIPT_NAME']       ?? 'N/A'),
                    'Gateway Interface' => self::escape($_SERVER['GATEWAY_INTERFACE'] ?? 'N/A'),
                    'OS'                => self::escape(PHP_OS),
                    'Architecture'      => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
                    'CPU Cores'         => $cpuCores,
                    'System Uptime'     => $uptime,
                    'PHP Process PID'   => (string) getmypid(),
                ])) .
                (!empty($envVars) ? self::buildSection('Environment Variables (' . count($envVars) . ')', self::buildParametersHtml($envVars)) : '') .
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
                            $memData[$key] = self::formatBytes($memInfo[$key] * 1024);
                        }
                    }
                    if (isset($memInfo['MemTotal'], $memInfo['MemAvailable']))
                    {
                        $memData['Used'] = self::formatBytes(($memInfo['MemTotal'] - $memInfo['MemAvailable']) * 1024);
                    }
                    if (!empty($memData))
                    {
                        $html .= self::buildSection('System Memory', self::buildParametersHtml($memData));
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
                        $html .= self::buildSection('System Load Average', self::buildParametersHtml([
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
                    $html   .= self::buildSection('Disk Usage', self::buildParametersHtml([
                        'Total'  => self::formatBytes((int) $total),
                        'Free'   => self::formatBytes((int) $free),
                        'Used'   => self::formatBytes((int) $used),
                        'Used %' => $usedPct . '%',
                    ]));
                }
            }

            return $html;
        }

        protected static function buildServerVarsSection(): string
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

                $data[self::escape((string) $k)] = self::escape($display);
            }

            if (empty($data))
            {
                return '';
            }

            ksort($data);
            return self::buildSection('$_SERVER (' . count($data) . ')', self::buildParametersHtml($data));
        }
    }
