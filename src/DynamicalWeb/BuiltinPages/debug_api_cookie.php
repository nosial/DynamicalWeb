<?php

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\RequestMethod;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Objects\Cookie;
    use DynamicalWeb\WebSession;

    // Only available when debug panel is enabled
    $instance = WebSession::getInstance();
    if ($instance === null || !$instance->getWebConfiguration()->getApplication()->isDebugPanelEnabled())
    {
        WebSession::getResponse()->setStatusCode(ResponseCode::NOT_FOUND);
        WebSession::getResponse()->setContentType(MimeType::TEXT);
        WebSession::getResponse()->setBody('Not Found');
        return;
    }

    $request = WebSession::getRequest();
    $response = WebSession::getResponse();

    // Only accept POST requests
    if ($request->getMethod() !== RequestMethod::POST)
    {
        $response->setStatusCode(ResponseCode::METHOD_NOT_ALLOWED);
        $response->setContentType(MimeType::JSON);
        $response->setBody(json_encode(['error' => 'Method not allowed']));
        return;
    }

    // The framework auto-parses JSON body into body parameters
    $data = $request->getBodyParameters();
    if (empty($data['name']))
    {
        $response->setStatusCode(ResponseCode::BAD_REQUEST);
        $response->setContentType(MimeType::JSON);
        $response->setBody(json_encode(['error' => 'Invalid request body, "name" is required']));
        return;
    }

    $name = (string) $data['name'];
    $delete = !empty($data['del']);

    if ($delete)
    {
        // Delete the cookie by setting it to expire in the past
        $response->setCookie(
            name:     $name,
            value:    '',
            expires:  time() - 86400,
            path:     (string) ($data['path'] ?? '/'),
            domain:   (string) ($data['domain'] ?? ''),
            secure:   !empty($data['secure']),
            httpOnly: false,
        );
    }
    else
    {
        $value    = (string) ($data['value'] ?? '');
        $path     = (string) ($data['path'] ?? '/');
        $domain   = (string) ($data['domain'] ?? '');
        $secure   = !empty($data['secure']);
        $sameSite = (string) ($data['samesite'] ?? 'Lax');
        $session  = $data['session'] ?? true;
        $expires  = 0;

        // Validate SameSite value
        if (!in_array($sameSite, ['None', 'Lax', 'Strict'], true))
        {
            $sameSite = 'Lax';
        }

        // Parse expiry
        if (!$session && !empty($data['expires']))
        {
            $parsed = strtotime((string) $data['expires']);
            if ($parsed !== false)
            {
                $expires = $parsed;
            }
        }

        $response->addCookie(new Cookie(
            name:     $name,
            value:    $value,
            expires:  $expires,
            path:     $path,
            domain:   $domain,
            secure:   $secure,
            httpOnly: false,
            sameSite: $sameSite,
        ));
    }

    $response->setContentType(MimeType::JSON);
    $response->setStatusCode(ResponseCode::OK);
    $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response->setBody(json_encode(['success' => true]));
