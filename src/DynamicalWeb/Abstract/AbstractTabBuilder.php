<?php

    namespace DynamicalWeb\Abstract;

    use DynamicalWeb\Objects\UserAgent;

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

        /**
         * Formats a byte size into a human-readable string with appropriate units.
         *
         * @param int $bytes The size in bytes.
         * @return string The formatted size string.
         */
        public static function formatBytes(int $bytes): string
        {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $bytes = max($bytes, 0);
            $pow   = min((int) floor($bytes ? log($bytes) / log(1024) : 0), count($units) - 1);

            return round($bytes / (1 << (10 * $pow)), 2) . ' ' . $units[$pow];
        }

        /**
         * Builds an HTML section with a title and content, styled as a divider in the table.
         *
         * @param string $title The title of the section.
         * @param string $content The HTML content of the section.
         * @return string The complete HTML for the section, including the divider row and content row.
         */
        protected static function buildSection(string $title, string $content): string
        {
            return '<tr><td colspan="6" class="dw-section-divider">' . $title . '</td></tr>' .
                   '<tr><td colspan="6" style="padding: 0;">' . $content . '</td></tr>';
        }

        /**
         * Builds an HTML representation of a set of parameters, displaying keys and values in a structured format.
         *
         * @param array $params An associative array of parameters to display, where keys are parameter names and values are parameter values.
         * @param string $emptyMessage A message to display if the parameters array is empty. Defaults to 'None'.
         * @return string The HTML representation of the parameters, or the empty message if no parameters are provided.
         */
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

        /**
         * Builds an HTML representation of a file item, displaying the file path and type in a structured format.
         *
         * @param string $escapedPath The escaped file path to display.
         * @param string $type The type of the file.
         * @return string The HTML representation of the file item.
         */
        protected static function buildFileItem(string $escapedPath, string $type): string
        {
            return '<div class="dw-file-item">'
                 . '<span class="dw-file-type">' . self::escape($type) . '</span>'
                 . $escapedPath
                 . '</div>';
        }

        /**
         * Builds an HTML representation of a user agent, displaying browser, operating system, device type, and other
         * relevant information in a structured format.
         *
         * @param UserAgent $userAgent The UserAgent object containing information about the user agent to display.
         * @return string The HTML representation of the user agent information, or a message indicating that no user agent was detected if the input is null.
         */
        protected static function buildUserAgentHtml(UserAgent $userAgent): string
        {
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

        /**
         * Builds an HTML preview of a raw body string, truncating it if it exceeds a certain length and providing a
         * visual indication that it has been truncated.
         *
         * @param string $body The raw body string to preview.
         * @return string The HTML representation of the raw body preview, including truncation indication if applicable.
         */
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
