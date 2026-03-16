<?php

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\WebSession;

    $instance = WebSession::getInstance();
    $request  = WebSession::getRequest();
    $response = WebSession::getResponse();

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Safely parse and validate a redirect target.
     * Accepts only same-origin paths (starting with /) or full URLs sharing
     * the same host/scheme as the current request. Returns null if invalid.
     */
    $safeRedirectTarget = static function(?string $raw) use ($request): ?string
    {
        if ($raw === null || $raw === '')
        {
            return null;
        }

        // Decode once — reject if double-encoded tricks are attempted
        $decoded = urldecode($raw);
        if (urldecode($decoded) !== $decoded)
        {
            return null;
        }

        // Reject anything with control characters or newlines (header injection)
        if (preg_match('/[\x00-\x1f\x7f]/', $decoded))
        {
            return null;
        }

        // Accept absolute paths on the same origin (e.g. "/about", "/?foo=bar")
        if (str_starts_with($decoded, '/') && !str_starts_with($decoded, '//'))
        {
            return $decoded;
        }

        // Accept full URLs only if they share scheme + host with current request
        $parsed = parse_url($decoded);
        if ($parsed === false || empty($parsed['host']))
        {
            return null;
        }

        $scheme      = strtolower($parsed['scheme'] ?? '');
        $host        = strtolower($parsed['host']);
        $currentHost = strtolower($request->getHost() ?? '');

        if (!in_array($scheme, ['http', 'https'], true))
        {
            return null;
        }

        if ($host !== $currentHost)
        {
            return null;
        }

        return $decoded;
    };

    // -------------------------------------------------------------------------
    // Extract and validate locale ID from path (/dynaweb/language/{id})
    // -------------------------------------------------------------------------

    $requestPath = $request->getPath();
    if (!preg_match('|^(?:.*/)?dynaweb/language/([^/?#]+)|i', $requestPath, $m))
    {
        $response->setStatusCode(ResponseCode::BAD_REQUEST);
        $response->setContentType(MimeType::TEXT);
        $response->setBody('Bad Request');
        return;
    }

    // Sanitize locale ID: only lowercase alphanumeric, hyphens, underscores; max 10 chars
    $rawLocale     = $m[1];
    $sanitized     = preg_replace('/[^a-z0-9_\-]/i', '', $rawLocale);
    $sanitized     = strtolower(substr($sanitized, 0, 10));

    $availableLocales = $instance ? $instance->getAvailableLocaleCodes() : [];

    // Validate against configured locales
    if ($sanitized === '' || !in_array($sanitized, $availableLocales, true))
    {
        // Unknown locale — fall back to configured default or first available
        $defaultLocale = $instance
            ? $instance->getWebConfiguration()->getApplication()->getDefaultLocale()
            : null;

        if ($defaultLocale !== null && in_array($defaultLocale, $availableLocales, true))
        {
            $sanitized = $defaultLocale;
        }
        elseif (!empty($availableLocales))
        {
            $sanitized = $availableLocales[0];
        }
        else
        {
            $response->setStatusCode(ResponseCode::NOT_FOUND);
            $response->setContentType(MimeType::TEXT);
            $response->setBody('No locales configured');
            return;
        }
    }

    // -------------------------------------------------------------------------
    // Determine redirect target
    // -------------------------------------------------------------------------

    $redirectTo = null;

    // Check "r" query parameter for a caller-specified return location
    $rParam = $request->getQueryParameters()['r'] ?? null;
    if (is_string($rParam))
    {
        $redirectTo = $safeRedirectTarget($rParam);
    }

    // Fall back: first non-dynaweb route defined in configuration
    if ($redirectTo === null && $instance !== null)
    {
        $routes = $instance->getWebConfiguration()->getRouter()->getRoutes();
        foreach ($routes as $route)
        {
            $path = $route->getPath();
            // Skip dynaweb and dynamic/parametric routes
            if (!str_starts_with($path, '/dynaweb') && !str_contains($path, '{'))
            {
                $redirectTo = $path;
                break;
            }
        }
    }

    // Ultimate fallback
    if ($redirectTo === null)
    {
        $redirectTo = '/';
    }

    // -------------------------------------------------------------------------
    // Set the locale cookie and redirect
    // -------------------------------------------------------------------------

    // Cookie lifetime: 1 year
    $expires  = time() + 31536000;
    $isSecure = $request->isSecure();

    $response->setCookie(
        name:     'locale',
        value:    $sanitized,
        expires:  $expires,
        secure:   $isSecure,
        httpOnly: true
    );

    $response->setRedirect($redirectTo, ResponseCode::FOUND);
