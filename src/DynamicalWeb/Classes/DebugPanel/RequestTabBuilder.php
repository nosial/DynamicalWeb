<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;
    use DynamicalWeb\Classes\DebugPanel as DebugPanelClass;
    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;

    class RequestTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $request = DebugPanelClass::$currentRequest;
            $meta = [];
            $meta['Client IP'] = self::escape($_SERVER['REMOTE_ADDR'] ?? 'N/A');
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $meta['Forwarded For'] = self::escape($_SERVER['HTTP_X_FORWARDED_FOR']);
            }
            if (!empty($_SERVER['HTTP_X_REAL_IP']))
            {
                $meta['Real IP'] = self::escape($_SERVER['HTTP_X_REAL_IP']);
            }
            $meta['Request Method'] = $request ? self::escape($request->getMethod()->value) : 'N/A';
            $meta['Request URI'] = self::escape($_SERVER['REQUEST_URI'] ?? 'N/A');
            $meta['HTTP Version'] = self::escape($_SERVER['SERVER_PROTOCOL'] ?? 'N/A');
            $httpsVal = $_SERVER['HTTPS'] ?? '';
            $meta['HTTPS'] = ($httpsVal === 'on' || $httpsVal === '1') ? 'Yes' : 'No';
            if (isset($_SERVER['REQUEST_TIME_FLOAT']))
            {
                $reqTime = (float) $_SERVER['REQUEST_TIME_FLOAT'];
                $agoMs = round((microtime(true) - $reqTime) * 1000, 1);
                $meta['Request Time'] = date('Y-m-d H:i:s', (int) $reqTime) . ' (' . $agoMs . 'ms ago)';
            }
            if (!empty($_SERVER['HTTP_CONNECTION']))
            {
                $meta['Connection'] = self::escape($_SERVER['HTTP_CONNECTION']);
            }
            if (!empty($_SERVER['HTTP_KEEP_ALIVE']))
            {
                $meta['Keep-Alive'] = self::escape($_SERVER['HTTP_KEEP_ALIVE']);
            }

            $html = self::buildSection('Connection & Request Metadata', self::buildParametersHtml($meta));

            $userAgentObj = $request ? $request->getUserAgent() : null;
            if ($userAgentObj !== null)
            {
                $html .= self::buildSection('User Agent Detection', self::buildUserAgentHtml($userAgentObj));
            }

            if ($request !== null)
            {
                $negotiation = [];
                foreach (['Accept', 'Accept-Language', 'Accept-Encoding', 'Accept-Charset'] as $h)
                {
                    $val = $request->getHeader($h);
                    if ($val !== null)
                    {
                        $negotiation[$h] = $val;
                    }
                }
                if (!empty($negotiation))
                {
                    $html .= self::buildSection('Content Negotiation', self::buildParametersHtml($negotiation));
                }
            }

            foreach ([
                'Query Parameters'  => $request ? $request->getQueryParameters() : [],
                'Body Parameters'   => $request ? $request->getBodyParameters()  : [],
                'Form Parameters'   => $request ? $request->getFormParameters()  : [],
                'Path Parameters'   => $request ? $request->getPathParameters()  : [],
                'Request Headers'   => $request ? $request->getHeaders()         : [],
                'Request Cookies'   => $request ? $request->getCookies()         : [],
            ] as $label => $data)
            {
                if (!empty($data))
                {
                    $count = count($data);
                    $html .= self::buildSection("$label ($count)", self::buildParametersHtml($data));
                }
            }

            if ($request && $request->hasFiles())
            {
                $html .= self::buildSection('Uploaded Files (' . $request->getFileCount() . ')', self::buildUploadsHtml($request->getFiles()));
            }

            if ($request !== null)
            {
                $method = $request->getMethod()->value;
                if (!in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true))
                {
                    $rawBody = $request->getRawBody();
                    if ($rawBody !== null && $rawBody !== '')
                    {
                        $html .= self::buildSection(
                            'Raw Request Body (' . self::formatBytes(strlen($rawBody)) . ')',
                            self::buildRawBodyPreviewHtml($rawBody)
                        );
                    }
                }
            }

            return $html;
        }

        /**
         * Builds the HTML for the uploaded files section of the debug panel.
         *
         * @param array $files The array of uploaded files, typically from $request->getFiles().
         * @return string The HTML content for the uploaded files section, or a message if no files are present.
         */
        protected static function buildUploadsHtml(array $files): string
        {
            if (empty($files))
            {
                return '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">No files</div>';
            }

            $html = '';

            foreach ($files as $fileData)
            {
                if (is_array($fileData['name'] ?? null))
                {
                    $count = count($fileData['name']);
                    for ($i = 0; $i < $count; $i++)
                    {
                        $error = (int) ($fileData['error'][$i] ?? UPLOAD_ERR_NO_FILE);
                        $valid = $error === UPLOAD_ERR_OK;
                        $html .= self::buildUploadFileRow(
                            (string) ($fileData['name'][$i] ?? 'unknown'),
                            (string) ($fileData['type'][$i] ?? 'unknown'),
                            (int)    ($fileData['size'][$i] ?? 0),
                            $valid,
                            $error
                        );
                    }
                }
                else
                {
                    $error = (int) ($fileData['error'] ?? UPLOAD_ERR_NO_FILE);
                    $valid = $error === UPLOAD_ERR_OK;
                    $html .= self::buildUploadFileRow(
                        (string) ($fileData['name'] ?? 'unknown'),
                        (string) ($fileData['type'] ?? 'unknown'),
                        (int)    ($fileData['size'] ?? 0),
                        $valid,
                        $error
                    );
                }
            }

            return $html ?: '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">No files</div>';
        }

        /**
         * Builds the HTML for a single uploaded file row in the debug panel.
         *
         * @param string $name The original name of the uploaded file.
         * @param string $type The MIME type of the uploaded file.
         * @param int $size The size of the uploaded file in bytes.
         * @param bool $valid Whether the upload was successful (error code UPLOAD_ERR_OK).
         * @param int $error The error code associated with the upload, if any.
         *
         * @return string The HTML content for a single uploaded file row, including validation status and error messages if applicable.
         */
        protected static function buildUploadFileRow(string $name, string $type, int $size, bool $valid, int $error): string
        {
            static $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'Exceeds ax file size directive',
                UPLOAD_ERR_PARTIAL    => 'Only partially uploaded',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write to disk',
                UPLOAD_ERR_EXTENSION  => 'Stopped by a PHP extension',
            ];

            $icon    = $valid ? '&#10003;' : '&#10007;';
            $color   = $valid ? '#27ae60'  : '#cc0000';
            $errNote = $valid ? '' : ' &bull; ' . ($errorMessages[$error] ?? 'Error ' . $error);

            return '<div class="dw-param-item">'
                 . '<span class="dw-param-key" style="color:' . $color . ';">' . $icon . ' ' . self::escape($name) . '</span>'
                 . '<span class="dw-param-value">' . self::escape($type) . ' &bull; ' . self::formatBytes($size) . $errNote . '</span>'
                 . '</div>';
        }

        /**
         * Builds the HTML for the security audit section of the debug panel, checking for common security headers.
         *
         * @param Response $response The response object to check for security headers.
         * @return string The HTML content for the security audit section, indicating which headers are present and their values if applicable.
         */
        public static function buildSecurityAuditHtml(Response $response): string
        {
            $headers = array_change_key_case($response->getHeaders(), CASE_LOWER);

            $checks = [
                'Content-Security-Policy'   => isset($headers['content-security-policy'])
                                                || isset($headers['content-security-policy-report-only']),
                'X-Frame-Options'           => isset($headers['x-frame-options']),
                'X-Content-Type-Options'    => isset($headers['x-content-type-options']),
                'Strict-Transport-Security' => isset($headers['strict-transport-security']),
                'Referrer-Policy'           => isset($headers['referrer-policy']),
                'Permissions-Policy'        => isset($headers['permissions-policy']),
            ];

            $html = '';
            foreach ($checks as $header => $present)
            {
                $icon  = $present ? '&#10003; Present'  : '&#9651; Missing';
                $color = $present ? '#27ae60'            : '#c47a00';
                $value = $present ? self::escape($headers[strtolower($header)] ?? $headers['content-security-policy-report-only'] ?? '') : '';
                $html .= '<div class="dw-param-item">'
                       . '<span class="dw-param-key" style="color:' . $color . ';">' . $icon . '</span>'
                       . '<span class="dw-param-value">'
                       . '<strong>' . self::escape($header) . '</strong>'
                       . ($value ? '<br><span style="color:#555;">' . $value . '</span>' : '')
                       . '</span>'
                       . '</div>';
            }

            return $html;
        }
    }
