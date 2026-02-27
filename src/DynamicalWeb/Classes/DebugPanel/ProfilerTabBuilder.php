<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Classes\DebugPanel as DebugPanelClass;

    class ProfilerTabBuilder
    {
        public static function build(array $files): string
        {
            if (empty($files))
            {
                return '';
            }

            $totalTime = microtime(true) - DebugPanelClass::$startTime;
            $prevTime  = DebugPanelClass::$startTime;
            $prevMem   = DebugPanelClass::$startMemory;

            $maxMem          = DebugPanelClass::$startMemory;
            $slowestFile     = '';
            $slowestDuration = 0;
            foreach ($files as $f)
            {
                if ($f['memory'] > $maxMem)
                {
                    $maxMem = $f['memory'];
                }
                $dur = $f['duration'] ?? 0;
                if ($dur > $slowestDuration)
                {
                    $slowestDuration = $dur;
                    $slowestFile     = $f['path'];
                }
            }
            $peakMemDelta = $maxMem - DebugPanelClass::$startMemory;
            $slowestFile  = preg_replace('/^ncc:\/\/[^\/]+\//', '', $slowestFile ?? '');

            $summaryHtml = '<div class="dw-param-item" style="padding:4px 12px;">'
                         . '<span class="dw-param-key">Files Executed</span><span class="dw-param-value">' . count($files) . '</span></div>'
                         . '<div class="dw-param-item" style="padding:4px 12px;">'
                         . '<span class="dw-param-key">Total Duration</span><span class="dw-param-value">' . Shared::formatTime($totalTime) . '</span></div>'
                         . '<div class="dw-param-item" style="padding:4px 12px;">'
                         . '<span class="dw-param-key">Peak Memory Delta</span><span class="dw-param-value">+' . Shared::formatBytes($peakMemDelta > 0 ? $peakMemDelta : 0) . '</span></div>'
                         . ($slowestFile ? '<div class="dw-param-item" style="padding:4px 12px;"><span class="dw-param-key">Slowest File</span><span class="dw-param-value">'
                            . Shared::escape($slowestFile) . ' (' . Shared::formatTime($slowestDuration) . ')</span></div>' : '');

            $html = '<table class="dw-profiler-table">'
                  . '<thead><tr>'
                  . '<th class="dw-profiler-col-type">Type</th>'
                  . '<th class="dw-profiler-col-file">File</th>'
                  . '<th class="dw-profiler-col-at">Started at</th>'
                  . '<th class="dw-profiler-col-dur">Duration</th>'
                  . '<th class="dw-profiler-col-pct">% Total</th>'
                  . '<th class="dw-profiler-col-mem">Memory &Delta;</th>'
                  . '</tr></thead><tbody>';

            foreach ($files as $file)
            {
                $path      = preg_replace('/^ncc:\/\/[^\/]+\//', '', $file['path']);
                $elapsed   = Shared::formatTime($file['time'] - DebugPanelClass::$startTime);
                $memDelta  = $file['memory'] - $prevMem;
                $memSign   = $memDelta >= 0 ? '+' : '';
                $memColor  = $memDelta > 0 ? '#c0392b' : ($memDelta < 0 ? '#27ae60' : '#888');

                $duration    = $file['duration'] ?? ($file['time'] - $prevTime);
                $durationPct = $totalTime > 0 ? min(100, round($duration / $totalTime * 100, 1)) : 0;
                $durationLbl = Shared::formatTime($duration);
                $estimated   = !isset($file['duration']);

                $bar = '<div class="dw-profiler-bar-track">'
                     . '<div class="dw-profiler-bar-fill" style="width:' . $durationPct . '%"></div>'
                     . '</div>';

                $html .= '<tr>'
                       . '<td><span class="dw-file-type">' . Shared::escape(strtoupper($file['type'])) . '</span></td>'
                       . '<td class="dw-profiler-col-file"><span class="dw-profiler-path">' . Shared::escape($path) . '</span></td>'
                       . '<td class="dw-profiler-col-at">' . $elapsed . '</td>'
                       . '<td class="dw-profiler-col-dur">' . ($estimated ? '<span title="Estimated" style="color:#aaa;">~</span>' : '') . $durationLbl . '</td>'
                       . '<td class="dw-profiler-col-pct">' . $bar . '<span class="dw-profiler-pct-label">' . $durationPct . '%</span></td>'
                       . '<td class="dw-profiler-col-mem" style="color:' . $memColor . ';">' . $memSign . Shared::escape(Shared::formatBytes($memDelta)) . '</td>'
                       . '</tr>';

                $prevTime = $file['time'];
                $prevMem  = $file['memory'];
            }

            $html .= '</tbody></table>';
            return $summaryHtml . $html;
        }

        public static function buildIncluded(array $files): string
        {
            if (empty($files))
            {
                return '';
            }

            $items = '';
            foreach ($files as $file)
            {
                $items .= Shared::buildFileItem(Shared::escape($file), 'PHP');
            }

            return '<div class="dw-file-list">' . $items . '</div>';
        }
    }
