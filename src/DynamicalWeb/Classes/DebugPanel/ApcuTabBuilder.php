<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;
    use DynamicalWeb\Classes\Apcu;

    class ApcuTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            if (!Apcu::isExtensionAvailable())
            {
                return '';
            }

            $html = '';

            $info = Apcu::cacheInfo(true);
            if ($info !== false && is_array($info))
            {
                $hits    = (int) ($info['num_hits']   ?? 0);
                $misses  = (int) ($info['num_misses'] ?? 0);
                $total   = $hits + $misses;
                $hitRate = $total > 0 ? round($hits / $total * 100, 1) . '%' : 'N/A';

                $html .= self::buildSection('APCu Cache Stats', self::buildParametersHtml([
                    'Cache Status'    => 'Enabled',
                    'Entries'         => (string) ($info['num_entries']    ?? 0),
                    'Hits'            => number_format($hits),
                    'Misses'          => number_format($misses),
                    'Hit Rate'        => $hitRate,
                    'Expunges'        => (string) ($info['expunges']       ?? 0),
                    'Start Time'      => isset($info['start_time']) ? date('Y-m-d H:i:s', (int) $info['start_time']) : 'N/A',
                    'Memory Used'     => self::formatBytes((int) ($info['mem_size'] ?? 0)),
                ]));
            }

            $sma = Apcu::smaInfo(true);
            if ($sma !== false && is_array($sma))
            {
                $total   = (int) ($sma['num_seg'] * $sma['seg_size']);
                $avail   = (int) ($sma['avail_mem'] ?? 0);
                $used    = $total - $avail;
                $usedPct = $total > 0 ? round($used / $total * 100, 1) . '%' : 'N/A';

                $html .= self::buildSection('APCu Shared Memory', self::buildParametersHtml([
                    'Segments'        => (string) ($sma['num_seg'] ?? 'N/A'),
                    'Segment Size'    => isset($sma['seg_size']) ? self::formatBytes((int) $sma['seg_size']) : 'N/A',
                    'Total Memory'    => self::formatBytes($total),
                    'Available'       => self::formatBytes($avail),
                    'Used'            => self::formatBytes($used) . ' (' . $usedPct . ')',
                ]));
            }

            return $html ?: '';
        }
    }
