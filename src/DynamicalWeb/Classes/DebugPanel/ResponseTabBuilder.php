<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Objects\Response;

    class ResponseTabBuilder
    {
        public static function build(Response $response): string
        {
            $code        = $response->getStatusCode();
            $statusColor = match(true) {
                $code->value >= 500 => '#c0392b',
                $code->value >= 400 => '#c47a00',
                $code->value >= 300 => '#2980b9',
                $code->value >= 200 => '#27ae60',
                default             => '#666',
            };
            $statusHtml = '<div class="dw-param-item"><span class="dw-param-key" style="color:' . $statusColor . ';">HTTP ' . $code->value . '</span>'
                        . '<span class="dw-param-value">' . Shared::escape(str_replace('_', ' ', $code->name)) . '</span></div>';
            $html = Shared::buildSection('Response Status', $statusHtml);

            $headers = $response->getHeaders();
            if (!empty($headers))
            {
                $html .= Shared::buildSection('Response Headers (' . count($headers) . ')', Shared::buildParametersHtml($headers));
            }

            $cookies = $response->getCookies();
            if (!empty($cookies))
            {
                $cookieMap = [];
                foreach ($cookies as $cookie)
                {
                    $parts   = [$cookie->getValue()];
                    $expires = $cookie->getExpires();
                    $expStr  = $expires === 0 ? 'Session' : date('Y-m-d H:i:s', $expires);
                    $parts[] = 'expires: "' . $expStr . '"';
                    $path    = $cookie->getPath();
                    if ($path !== '' && $path !== '/')
                    {
                        $parts[] = 'path: ' . $path;
                    }
                    $domain = $cookie->getDomain();
                    if ($domain !== '')
                    {
                        $parts[] = 'domain: ' . $domain;
                    }
                    if ($cookie->isSecure())
                    {
                        $parts[] = 'secure';
                    }
                    if ($cookie->isHttpOnly())
                    {
                        $parts[] = 'httpOnly';
                    }
                    $cookieMap[$cookie->getName()] = implode(' | ', $parts);
                }
                $html .= Shared::buildSection('Response Cookies (' . count($cookies) . ')', Shared::buildParametersHtml($cookieMap));
            }

            $html .= Shared::buildSection('Security Headers', RequestTabBuilder::buildSecurityAuditHtml($response));

            $corsKeys     = ['access-control-allow-origin', 'access-control-allow-methods', 'access-control-allow-headers',
                             'access-control-allow-credentials', 'access-control-expose-headers', 'access-control-max-age'];
            $lowerHeaders = array_change_key_case($headers, CASE_LOWER);
            $corsData     = [];
            foreach ($corsKeys as $key)
            {
                if (isset($lowerHeaders[$key]))
                {
                    $corsData[$key] = $lowerHeaders[$key];
                }
            }
            if (!empty($corsData))
            {
                $html .= Shared::buildSection('CORS Headers', Shared::buildParametersHtml($corsData));
            }

            return $html;
        }
    }
