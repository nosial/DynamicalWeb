<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;

    class OpcacheTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            return self::buildOpcacheStatsSection() . self::buildOpcacheConfigSection();
        }

        /**
         * Builds the OPcache statistics section of the debug panel.
         *
         * @return string The HTML content for the OPcache statistics section.
         */
        protected static function buildOpcacheStatsSection(): string
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
                $data['Memory Used']   = self::formatBytes((int) ($mem['used_memory']  ?? 0));
                $data['Memory Free']   = self::formatBytes((int) ($mem['free_memory']  ?? 0));
                $data['Memory Wasted'] = self::formatBytes((int) ($mem['wasted_memory'] ?? 0))
                                       . ' (' . round((float) ($mem['current_wasted_percentage'] ?? 0), 2) . '%)';
            }

            if (isset($ocs['interned_strings_usage']['used_memory']))
            {
                $data['Interned Strings'] = self::formatBytes((int) $ocs['interned_strings_usage']['used_memory']);
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

            $result = self::buildSection('OPcache Statistics', self::buildParametersHtml($data));

            if (!empty($ocs['jit']))
            {
                $jit    = $ocs['jit'];
                $result .= self::buildSection('JIT Compiler', self::buildParametersHtml([
                    'JIT Status'  => !empty($jit['enabled']) ? 'Enabled' : 'Disabled',
                    'JIT On'      => !empty($jit['on'])      ? 'Yes'     : 'No',
                    'Buffer Size' => self::formatBytes((int) ($jit['buffer_size'] ?? 0)),
                    'Buffer Free' => self::formatBytes((int) ($jit['buffer_free'] ?? 0)),
                ]));
            }

            return $result;
        }

        /**
         * Builds the OPcache configuration section of the debug panel.
         *
         * @return string The HTML content for the OPcache configuration section.
         */
        protected static function buildOpcacheConfigSection(): string
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
                $display = is_bool($val) ? ($val ? 'On' : 'Off') : self::escape((string) $val);
                $data[self::escape($key)] = $display;
            }

            if (empty($data))
            {
                return '';
            }

            return self::buildSection('OPcache Configuration', self::buildParametersHtml($data));
        }

        /**
         * Builds the Realpath Cache section of the debug panel.
         *
         * @return string The HTML content for the Realpath Cache section, or an empty string if not supported.
         */
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
            if ($size !== null)  $data['Used Size']   = self::formatBytes($size);
            if ($max)            $data['Max Size']     = self::escape($max);
            if ($count !== null) $data['Cached Paths'] = (string) $count;
            if ($ttl)            $data['TTL']          = $ttl . 's';

            return self::buildSection('Realpath Cache', self::buildParametersHtml($data));
        }

        /**
         * Builds the Garbage Collector section of the debug panel.
         *
         * @return string The HTML content for the Garbage Collector section, or an empty string if not supported.
         */
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

            return self::buildSection('PHP Garbage Collector', self::buildParametersHtml($data));
        }
    }
