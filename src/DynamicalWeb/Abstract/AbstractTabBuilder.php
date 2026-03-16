<?php

    namespace DynamicalWeb\Abstract;

    abstract class AbstractTabBuilder
    {
        /**
         * Builds the HTML content for the tab.
         *
         * @return string The HTML content of the tab.
         */
        abstract public static function build(): string;

        /**
         * Escapes a string for safe output in HTML.
         *
         * @param string $str The string to escape.
         * @return string The escaped string.
         */
        protected static function escape(string $str): string
        {
            return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        /**
         * Formats a time duration in seconds into a human-readable string with appropriate units.
         *
         * @param float $seconds The time duration in seconds.
         * @return string The formatted time string.
         */
        protected static function formatTime(float $seconds): string
        {
            if ($seconds < 0.001)
            {
                return number_format($seconds * 1_000_000, 2) . ' μs';
            }

            if ($seconds < 1)
            {
                return number_format($seconds * 1_000, 2) . ' ms';
            }

            return number_format($seconds, 3) . ' s';
        }

        protected static function formatBytes(int $bytes): string
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow   = min((int) floor($bytes ? log($bytes) / log(1024) : 0), count($units) - 1);

            return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
        }

        protected static function buildSection(string $title, string $content): string
        {
            return '<tr><td colspan="6" class="dw-section-divider">' . $title . '</td></tr>' .
                   '<tr><td colspan="6" style="padding: 0;">' . $content . '</td></tr>';
        }

        protected static function buildParametersHtml(array $params, string $emptyMessage = 'None'): string
        {
            if (empty($params))
            {
                return '<div style="padding: 8px; font-style: italic; color: #999; text-align: center;">' . $emptyMessage . '</div>';
            }

            $html = '';
            foreach ($params as $key => $value)
            {
                $displayValue = self::escape(is_array($value) ? json_encode($value) : (string) $value);

                if (strlen($displayValue) > 100)
                {
                    $displayValue = substr($displayValue, 0, 100) . '...';
                }

                $html .= '<div class="dw-param-item">'
                       . '<span class="dw-param-key">' . self::escape((string) $key) . '</span>'
                       . '<span class="dw-param-value">' . $displayValue . '</span>'
                       . '</div>';
            }

            return $html;
        }

        protected static function buildFileItem(string $escapedPath, string $type): string
        {
            return '<div class="dw-file-item">'
                 . '<span class="dw-file-type">' . self::escape($type) . '</span>'
                 . $escapedPath
                 . '</div>';
        }

        protected static function buildUserAgentHtml($userAgent): string
        {
            if ($userAgent === null)
            {
                return '<div style="padding: 8px; font-style: italic; color: #999; text-align: center;">No user agent detected</div>';
            }

            $data = [
                'Browser'          => $userAgent->getFullBrowserName()  ?? 'Unknown',
                'Operating System' => $userAgent->getFullOsName()       ?? 'Unknown',
                'Device Type'      => $userAgent->getDeviceType()->value,
            ];

            if (($device = $userAgent->getFullDeviceName()) !== null)
            {
                $data['Device'] = $device;
            }

            if (($engine = $userAgent->getFullEngineName()) !== null)
            {
                $data['Rendering Engine'] = $engine;
            }

            $flags = array_filter([
                $userAgent->isMobile()  ? 'Mobile'  : null,
                $userAgent->isTablet()  ? 'Tablet'  : null,
                $userAgent->isDesktop() ? 'Desktop' : null,
                $userAgent->isBot()     ? 'Bot'     : null,
            ]);

            if (!empty($flags))
            {
                $data['Flags'] = implode(', ', $flags);
            }

            if ($userAgent->isBot())
            {
                $data['Bot Name'] = $userAgent->getBotName()?->value ?? 'Unknown Bot';
            }

            return self::buildParametersHtml($data);
        }

        protected static function buildRawBodyPreviewHtml(string $body): string
        {
            $limit   = 1000;
            $preview = mb_substr($body, 0, $limit, 'UTF-8');
            $clipped = mb_strlen($body, 'UTF-8') > $limit;

            return '<div style="padding:8px 12px;">'
                 . '<pre style="margin:0;font-size:10px;white-space:pre-wrap;word-break:break-all;color:#333;background:#f9f9f9;border:1px solid #e0e0e0;padding:6px;max-height:200px;overflow-y:auto;">'
                 . self::escape($preview)
                 . ($clipped ? "\n<span style='color:#888;font-style:italic;'>… truncated</span>" : '')
                 . '</pre>'
                 . '</div>';
        }
    }
