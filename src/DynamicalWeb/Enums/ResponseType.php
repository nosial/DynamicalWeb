<?php

    namespace DynamicalWeb\Enums;

    enum ResponseType
    {
        /**
         * Represents a basic response type, typically used for standard HTTP responses using .phtml or .php files
         */
        case BASIC;

        /**
         * Represents a file download response type, used when the response is intended to trigger a file download in
         * the client's browser
         */
        case FILE_DOWNLOAD;

        /**
         * Represents a JSON response type, where the response body contains JSON-encoded data
         */
        case JSON;

        /**
         * Represents a YAML response type, where the response body contains YAML-encoded data
         */
        case YAML;

        /**
         * Represents a redirect response type, used to redirect the client to another URL
         */
        case REDIRECT;

        /**
         * Represents a streaming response type, where content is generated dynamically and sent in real-time
         */
        case STREAM;
    }
