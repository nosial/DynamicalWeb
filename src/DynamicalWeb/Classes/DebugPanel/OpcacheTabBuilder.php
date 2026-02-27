<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class OpcacheTabBuilder
    {
        public static function build(): string
        {
            return self::buildOpcacheStatsSection() . self::buildOpcacheConfigSection();
        }

        public static function buildOpcacheStatsSection(): string
        {
            if (!function_exists('opcache_get_status'))
            {
                return '';
            }

            $ocs = @opcache_get_status(false);
            if ($ocs === false || empty($ocs['opcache_enabled']))
            {
                return '';
            }

            $stats = $ocs['opcache_statistics'] ?? [];
            $mem   = $ocs['memory_usage']       ?? [];
            $data  = [];

            if (!empty($mem))
            {
                $data['Memory Used']   = Shared::formatBytes((int) ($mem['used_memory']  ?? 0));
                $data['Memory Free']   = Shared::formatBytes((int) ($mem['free_memory']  ?? 0));
                $data['Memory Wasted'] = Shared::formatBytes((int) ($mem['wasted_memory'] ?? 0))
                                       . ' (' . round((float) ($mem['current_wasted_percentage'] ?? 0), 2) . '%)';
            }

            if (isset($ocs['interned_strings_usage']['used_memory']))
            {
                $data['Interned Strings'] = Shared::formatBytes((int) $ocs['interned_strings_usage']['used_memory']);
            }

            if (!empty($stats))
            {
                $data['Cached Scripts']   = (string) ($stats['num_cached_scripts'] ?? 0);
                $data['Cached Keys']      = (string) ($stats['num_cached_keys']    ?? 0);
                $data['Max Cached Keys']  = (string) ($stats['max_cached_keys']    ?? 0);
                $data['Hits']             = number_format((int) ($stats['hits']             ?? 0));
                $data['Misses']           = number_format((int) ($stats['misses']           ?? 0));
                $data['Hit Rate']         = round((float) ($stats['opcache_hit_rate']       ?? 0), 2) . '%';
                $data['Blacklist Misses'] = number_format((int) ($stats['blacklist_misses'] ?? 0));
                $lastRestart              = (int) ($stats['last_restart_time']               ?? 0);
                $data['Last Restart Time'] = $lastRestart > 0 ? date('Y-m-d H:i:s', $lastRestart) : 'Never';
            }

            $result = Shared::buildSection('OPcache Statistics', Shared::buildParametersHtml($data));

            if (!empty($ocs['jit']))
            {
                $jit    = $ocs['jit'];
                $result .= Shared::buildSection('JIT Compiler', Shared::buildParametersHtml([
                    'JIT Status'  => !empty($jit['enabled']) ? 'Enabled' : 'Disabled',
                    'JIT On'      => !empty($jit['on'])      ? 'Yes'     : 'No',
                    'Buffer Size' => Shared::formatBytes((int) ($jit['buffer_size'] ?? 0)),
                    'Buffer Free' => Shared::formatBytes((int) ($jit['buffer_free'] ?? 0)),
                ]));
            }

            return $result;
        }

        public static function buildOpcacheConfigSection(): string
        {
            if (!function_exists('opcache_get_configuration'))
            {
                return '';
            }

            $cfg = @opcache_get_configuration();
            if ($cfg === false || empty($cfg['directives']))
            {
                return '';
            }

            $keys = [
                'opcache.enable', 'opcache.enable_cli', 'opcache.memory_consumption',
                'opcache.interned_strings_buffer', 'opcache.max_accelerated_files',
                'opcache.revalidate_freq', 'opcache.validate_timestamps',
                'opcache.save_comments', 'opcache.fast_shutdown',
                'opcache.file_cache', 'opcache.jit', 'opcache.jit_buffer_size',
            ];

            $data = [];
            foreach ($keys as $key)
            {
                if (!array_key_exists($key, $cfg['directives']))
                {
                    continue;
                }
                $val = $cfg['directives'][$key];
                $display = is_bool($val) ? ($val ? 'On' : 'Off') : Shared::escape((string) $val);
                $data[Shared::escape($key)] = $display;
            }

            if (empty($data))
            {
                return '';
            }

            return Shared::buildSection('OPcache Configuration', Shared::buildParametersHtml($data));
        }

        public static function buildRealpathCacheSection(): string
        {
            $size  = function_exists('realpath_cache_size') ? realpath_cache_size() : null;
            $count = function_exists('realpath_cache_get')  ? count(realpath_cache_get()) : null;
            $max   = ini_get('realpath_cache_size');
            $ttl   = ini_get('realpath_cache_ttl');

            if ($size === null && $count === null)
            {
                return '';
            }

            $data = [];
            if ($size !== null)  $data['Used Size']   = Shared::formatBytes($size);
            if ($max)            $data['Max Size']     = Shared::escape($max);
            if ($count !== null) $data['Cached Paths'] = (string) $count;
            if ($ttl)            $data['TTL']          = $ttl . 's';

            return Shared::buildSection('Realpath Cache', Shared::buildParametersHtml($data));
        }

        public static function buildGcStatusSection(): string
        {
            if (!function_exists('gc_status'))
            {
                return '';
            }

            $gc = gc_status();

            $data = [
                'GC Enabled'       => $gc['running'] ? 'Running' : (ini_get('zend.enable_gc') ? 'Enabled' : 'Disabled'),
                'Runs'             => (string) ($gc['runs']      ?? 0),
                'Collected Cycles' => (string) ($gc['collected'] ?? 0),
                'Roots'            => (string) ($gc['roots']     ?? 0),
                'Threshold'        => (string) ($gc['threshold'] ?? 'N/A'),
            ];

            return Shared::buildSection('PHP Garbage Collector', Shared::buildParametersHtml($data));
        }
    }
