<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class PhpTabBuilder
    {
        public static function build(): string
        {
            $opcacheStatus = 'N/A';
            if (function_exists('opcache_get_status'))
            {
                $ocs = @opcache_get_status(false);
                $opcacheStatus = ($ocs && ($ocs['opcache_enabled'] ?? false))
                    ? 'Enabled (' . number_format($ocs['opcache_statistics']['opcache_hit_rate'] ?? 0, 1) . '% hit rate)'
                    : 'Disabled';
            }

            return
                Shared::buildSection('PHP Environment', Shared::buildParametersHtml([
                    'PHP Version'        => Shared::escape(PHP_VERSION),
                    'PHP Version ID'     => (string) PHP_VERSION_ID,
                    'SAPI'               => Shared::escape(php_sapi_name() ?: 'N/A'),
                    'Memory Limit'       => Shared::escape(ini_get('memory_limit')),
                    'Max Exec Time'      => ini_get('max_execution_time') . 's',
                    'Display Errors'     => ini_get('display_errors') ? 'On' : 'Off',
                    'Error Reporting'    => Shared::escape(self::errorReportingToString((int) ini_get('error_reporting'))),
                    'Upload Max'         => Shared::escape(ini_get('upload_max_filesize')),
                    'Post Max Size'      => Shared::escape(ini_get('post_max_size')),
                    'Default Charset'    => Shared::escape(ini_get('default_charset') ?: 'UTF-8'),
                    'Error Log'          => Shared::escape(ini_get('error_log') ?: 'Default'),
                    'Log Errors'         => ini_get('log_errors') ? 'On' : 'Off',
                    'Timezone'           => Shared::escape(ini_get('date.timezone') ?: date_default_timezone_get()),
                    'Short Open Tags'    => ini_get('short_open_tag') ? 'On' : 'Off',
                    'Zend Thread Safety' => (defined('PHP_ZTS') && PHP_ZTS) ? 'Yes' : 'No',
                    'OPcache'            => $opcacheStatus,
                    'Extensions Loaded'  => (string) count(get_loaded_extensions()),
                    'Included Files'     => (string) count(get_included_files()),
                ])) .
                Shared::buildSection('PHP Definitions', Shared::buildParametersHtml([
                    'ini File'            => Shared::escape(php_ini_loaded_file() ?: 'None'),
                    'Scanned ini Dirs'    => Shared::escape(php_ini_scanned_files() ?: 'None'),
                    'System Constants'    => (string) count(array_merge(...array_values(array_filter(get_defined_constants(true), static fn($k) => $k !== 'user', ARRAY_FILTER_USE_KEY)))),
                    'User Constants'      => (string) count(get_defined_constants(true)['user'] ?? []),
                    'User Functions'      => (string) count(get_defined_functions()['user'] ?? []),
                    'Internal Functions'  => (string) count(get_defined_functions()['internal'] ?? []),
                    'Declared Classes'    => (string) count(get_declared_classes()),
                    'Declared Interfaces' => (string) count(get_declared_interfaces()),
                    'Declared Traits'     => (string) count(get_declared_traits()),
                    'PHP_INT_MAX'         => (string) PHP_INT_MAX,
                    'PHP_INT_MIN'         => (string) PHP_INT_MIN,
                    'PHP_INT_SIZE'        => PHP_INT_SIZE . ' bytes',
                    'PHP_FLOAT_EPSILON'   => (string) PHP_FLOAT_EPSILON,
                    'PHP_FLOAT_MAX'       => (string) PHP_FLOAT_MAX,
                    'PHP_MAXPATHLEN'      => (string) PHP_MAXPATHLEN,
                    'PHP_OS_FAMILY'       => Shared::escape(PHP_OS_FAMILY),
                    'PHP_EXTRA_VERSION'   => Shared::escape(PHP_EXTRA_VERSION ?: 'None'),
                    'PHP_EOL'             => PHP_EOL === "\n" ? 'LF (\\n)' : (PHP_EOL === "\r\n" ? 'CRLF (\\r\\n)' : 'CR (\\r)'),
                    'PHP_PREFIX'          => Shared::escape(PHP_PREFIX),
                    'PHP_BINARY'          => Shared::escape(PHP_BINARY),
                    'PHP_SYSCONFDIR'      => Shared::escape(PHP_SYSCONFDIR),
                ])) .
                OpcacheTabBuilder::buildRealpathCacheSection() .
                OpcacheTabBuilder::buildGcStatusSection() .
                self::buildStreamWrappersSection() .
                self::buildSecurityIniSection();
        }

        public static function buildStreamWrappersSection(): string
        {
            $wrappers = stream_get_wrappers();
            sort($wrappers);
            return Shared::buildSection(
                'Stream Wrappers (' . count($wrappers) . ')',
                '<div class="dw-param-item"><span class="dw-param-value" style="font-family:monospace;">'
                . implode(', ', array_map(static fn($w) => Shared::escape($w) . '://', $wrappers))
                . '</span></div>'
            );
        }

        public static function buildSecurityIniSection(): string
        {
            $disableFunctions = ini_get('disable_functions') ?: 'None';
            if (strlen($disableFunctions) > 200)
            {
                $disableFunctions = substr($disableFunctions, 0, 200) . '...';
            }

            return Shared::buildSection('PHP Security', Shared::buildParametersHtml([
                'disable_functions'  => $disableFunctions,
                'disable_classes'    => ini_get('disable_classes')    ?: 'None',
                'open_basedir'       => ini_get('open_basedir')       ?: 'None (unrestricted)',
                'allow_url_fopen'    => ini_get('allow_url_fopen')    ? 'On'             : 'Off',
                'allow_url_include'  => ini_get('allow_url_include')  ? 'On (DANGEROUS)' : 'Off',
                'expose_php'         => ini_get('expose_php')         ? 'On'             : 'Off',
                'enable_dl'          => ini_get('enable_dl')          ? 'On'             : 'Off',
                'file_uploads'       => ini_get('file_uploads')       ? 'On'             : 'Off',
                'max_file_uploads'   => (string) ini_get('max_file_uploads'),
            ]));
        }


        public static function buildPhpExtensionsHtml(): string
        {
            $exts = get_loaded_extensions();
            sort($exts);
            $data = [];
            foreach ($exts as $ext)
            {
                $ver = phpversion($ext);
                $data[Shared::escape($ext)] = $ver ? Shared::escape($ver) : 'bundled';
            }
            return Shared::buildParametersHtml($data);
        }

        public static function buildPhpUserConstantsSection(): string
        {
            $all = get_defined_constants(true);

            $userConstants   = $all['user'] ?? [];
            $systemConstants = [];
            foreach ($all as $category => $constants)
            {
                if ($category !== 'user')
                {
                    foreach ($constants as $k => $v)
                    {
                        $systemConstants[(string) $k] = $v;
                    }
                }
            }
            ksort($systemConstants);

            $result = '';

            if (!empty($systemConstants))
            {
                $data = [];
                foreach ($systemConstants as $name => $value)
                {
                    $data[Shared::escape($name)] = Shared::escape(
                        is_bool($value)  ? ($value ? 'true' : 'false') :
                        (is_null($value) ? 'null' :
                        (is_array($value) ? json_encode($value) : (string) $value))
                    );
                }
                $result .= Shared::buildSection('System-Defined Constants (' . count($systemConstants) . ')', Shared::buildParametersHtml($data));
            }

            if (!empty($userConstants))
            {
                $data = [];
                foreach ($userConstants as $name => $value)
                {
                    $data[Shared::escape((string) $name)] = Shared::escape(
                        is_bool($value)  ? ($value ? 'true' : 'false') :
                        (is_null($value) ? 'null' :
                        (is_array($value) ? json_encode($value) : (string) $value))
                    );
                }
                $result .= Shared::buildSection('User-Defined Constants (' . count($userConstants) . ')', Shared::buildParametersHtml($data));
            }

            return $result;
        }

        private static function errorReportingToString(int $level): string
        {
            if ($level === 0)     return 'None';
            if ($level === E_ALL) return 'E_ALL';

            $flags = [
                E_ERROR             => 'E_ERROR',
                E_WARNING           => 'E_WARNING',
                E_PARSE             => 'E_PARSE',
                E_NOTICE            => 'E_NOTICE',
                E_DEPRECATED        => 'E_DEPRECATED',
                E_STRICT            => 'E_STRICT',
                E_CORE_ERROR        => 'E_CORE_ERROR',
                E_CORE_WARNING      => 'E_CORE_WARNING',
                E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
                E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
                E_USER_ERROR        => 'E_USER_ERROR',
                E_USER_WARNING      => 'E_USER_WARNING',
                E_USER_NOTICE       => 'E_USER_NOTICE',
                E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
                E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            ];

            $active = [];
            foreach ($flags as $bit => $name)
            {
                if (($level & $bit) === $bit)
                {
                    $active[] = $name;
                }
            }

            return $active ? implode(' | ', $active) : (string) $level;
        }
    }
