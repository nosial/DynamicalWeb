<?php

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\WebSession;

    WebSession::getResponse()->setContentType(MimeType::TEXT);
    WebSession::getResponse()->setStatusCode(ResponseCode::OK);
    WebSession::getResponse()->setBody('OK');