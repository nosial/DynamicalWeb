<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\Objects\Response;

    class RequestTabBuilder
    {
        public static function build(?Request $request): string
        {
            $meta = [];
            $meta['Client IP'] = Shared::escape($_SERVER['REMOTE_ADDR'] ?? 'N/A');
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
            {
                $meta['Forwarded For'] = Shared::escape($_SERVER['HTTP_X_FORWARDED_FOR']);
            }
            if (!empty($_SERVER['HTTP_X_REAL_IP']))
            {
                $meta['Real IP'] = Shared::escape($_SERVER['HTTP_X_REAL_IP']);
            }
            $meta['Request Method'] = $request ? Shared::escape($request->getMethod()->value) : 'N/A';
            $meta['Request URI'] = Shared::escape($_SERVER['REQUEST_URI'] ?? 'N/A');
            $meta['HTTP Version'] = Shared::escape($_SERVER['SERVER_PROTOCOL'] ?? 'N/A');
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
                $meta['Connection'] = Shared::escape($_SERVER['HTTP_CONNECTION']);
            }
            if (!empty($_SERVER['HTTP_KEEP_ALIVE']))
            {
                $meta['Keep-Alive'] = Shared::escape($_SERVER['HTTP_KEEP_ALIVE']);
            }

            $html = Shared::buildSection('Connection & Request Metadata', Shared::buildParametersHtml($meta));

            $userAgentObj = $request ? $request->getUserAgent() : null;
            if ($userAgentObj !== null)
            {
                $html .= Shared::buildSection('User Agent Detection', Shared::buildUserAgentHtml($userAgentObj));
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
                    $html .= Shared::buildSection('Content Negotiation', Shared::buildParametersHtml($negotiation));
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
                    $html .= Shared::buildSection("$label ($count)", Shared::buildParametersHtml($data));
                }
            }

            if ($request && $request->hasFiles())
            {
                $html .= Shared::buildSection('Uploaded Files (' . $request->getFileCount() . ')', self::buildUploadsHtml($request->getFiles()));
            }

            if ($request !== null)
            {
                $method = $request->getMethod()->value;
                if (!in_array(strtoupper($method), ['GET', 'HEAD', 'OPTIONS'], true))
                {
                    $rawBody = $request->getRawBody();
                    if ($rawBody !== null && $rawBody !== '')
                    {
                        $html .= Shared::buildSection(
                            'Raw Request Body (' . Shared::formatBytes(strlen($rawBody)) . ')',
                            Shared::buildRawBodyPreviewHtml($rawBody)
                        );
                    }
                }
            }

            return $html;
        }

        public static function buildUploadsHtml(array $files): string
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

        public static function buildUploadFileRow(string $name, string $type, int $size, bool $valid, int $error): string
        {
            static $errorMessages = [
                UPLOAD_ERR_INI_SIZE   => 'Exceeds upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE  => 'Exceeds MAX_FILE_SIZE directive',
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
                 . '<span class="dw-param-key" style="color:' . $color . ';">' . $icon . ' ' . Shared::escape($name) . '</span>'
                 . '<span class="dw-param-value">' . Shared::escape($type) . ' &bull; ' . Shared::formatBytes($size) . $errNote . '</span>'
                 . '</div>';
        }

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
                $value = $present ? Shared::escape($headers[strtolower($header)] ?? $headers['content-security-policy-report-only'] ?? '') : '';
                $html .= '<div class="dw-param-item">'
                       . '<span class="dw-param-key" style="color:' . $color . ';">' . $icon . '</span>'
                       . '<span class="dw-param-value">'
                       . '<strong>' . Shared::escape($header) . '</strong>'
                       . ($value ? '<br><span style="color:#555;">' . $value . '</span>' : '')
                       . '</span>'
                       . '</div>';
            }

            return $html;
        }
    }
