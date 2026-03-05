<?php

    namespace DynamicalWeb\Enums;

    use DynamicalWeb\Interfaces\StringInterface;

    enum ResponseCode : int implements StringInterface
    {
        // Informational
        case CONTINUE = 100;
        case SWITCHING_PROTOCOLS = 101;
        case PROCESSING = 102;
        case EARLY_HINTS = 103;

        // Success
        case OK = 200;
        case CREATED = 201;
        case ACCEPTED = 202;
        case NON_AUTHORITATIVE_INFORMATION = 203;
        case NO_CONTENT = 204;
        case RESET_CONTENT = 205;
        case PARTIAL_CONTENT = 206;
        case MULTI_STATUS = 207;
        case ALREADY_REPORTED = 208;
        case IM_USED = 226;

        // Redirection
        case MULTIPLE_CHOICES = 300;
        case MOVED_PERMANENTLY = 301;
        case FOUND = 302;
        case SEE_OTHER = 303;
        case NOT_MODIFIED = 304;
        case USE_PROXY = 305;
        case UNUSED = 306;
        case TEMPORARY_REDIRECT = 307;
        case PERMANENT_REDIRECT = 308;

        // Client Errors
        case BAD_REQUEST = 400;
        case UNAUTHORIZED = 401;
        case PAYMENT_REQUIRED = 402;
        case FORBIDDEN = 403;
        case NOT_FOUND = 404;
        case METHOD_NOT_ALLOWED = 405;
        case NOT_ACCEPTABLE = 406;
        case PROXY_AUTHENTICATION_REQUIRED = 407;
        case REQUEST_TIMEOUT = 408;
        case CONFLICT = 409;
        case GONE = 410;
        case LENGTH_REQUIRED = 411;
        case PRECONDITION_FAILED = 412;
        case PAYLOAD_TOO_LARGE = 413;
        case URI_TOO_LONG = 414;
        case UNSUPPORTED_MEDIA_TYPE = 415;
        case RANGE_NOT_SATISFIABLE = 416;
        case EXPECTATION_FAILED = 417;
        case IM_A_TEAPOT = 418;
        case MISDIRECTED_REQUEST = 421;
        case UNPROCESSABLE_ENTITY = 422;
        case LOCKED = 423;
        case FAILED_DEPENDENCY = 424;
        case TOO_EARLY = 425;
        case UPGRADE_REQUIRED = 426;
        case PRECONDITION_REQUIRED = 428;
        case TOO_MANY_REQUESTS = 429;
        case REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
        case UNAVAILABLE_FOR_LEGAL_REASONS = 451;

        // Server Errors
        case INTERNAL_SERVER_ERROR = 500;
        case NOT_IMPLEMENTED = 501;
        case BAD_GATEWAY = 502;
        case SERVICE_UNAVAILABLE = 503;
        case GATEWAY_TIMEOUT = 504;
        case HTTP_VERSION_NOT_SUPPORTED = 505;
        case VARIANT_ALSO_NEGOTIATES = 506;
        case INSUFFICIENT_STORAGE = 507;
        case LOOP_DETECTED = 508;
        case NOT_EXTENDED = 510;
        case NETWORK_AUTHENTICATION_REQUIRED = 511;

        /**
         * Retrieves the standard message associated with the HTTP response code.
         *
         * @return string The standard message for the HTTP response code.
         */
        public function getMessage(): string
        {
            return match($this)
            {
                self::CONTINUE => 'Continue',
                self::SWITCHING_PROTOCOLS => 'Switching Protocols',
                self::PROCESSING => 'Processing',
                self::EARLY_HINTS => 'Early Hints',
                self::OK => 'OK',
                self::CREATED => 'Created',
                self::ACCEPTED => 'Accepted',
                self::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
                self::NO_CONTENT => 'No Content',
                self::RESET_CONTENT => 'Reset Content',
                self::PARTIAL_CONTENT => 'Partial Content',
                self::MULTI_STATUS => 'Multi-Status',
                self::ALREADY_REPORTED => 'Already Reported',
                self::IM_USED => 'IM Used',
                self::MULTIPLE_CHOICES => 'Multiple Choices',
                self::MOVED_PERMANENTLY => 'Moved Permanently',
                self::FOUND => 'Found',
                self::SEE_OTHER => 'See Other',
                self::NOT_MODIFIED => 'Not Modified',
                self::USE_PROXY => 'Use Proxy',
                self::UNUSED => '(Unused)',
                self::TEMPORARY_REDIRECT => 'Temporary Redirect',
                self::PERMANENT_REDIRECT => 'Permanent Redirect',
                self::BAD_REQUEST => 'Bad Request',
                self::UNAUTHORIZED => 'Unauthorized',
                self::PAYMENT_REQUIRED => 'Payment Required',
                self::FORBIDDEN => 'Forbidden',
                self::NOT_FOUND => 'Not Found',
                self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
                self::NOT_ACCEPTABLE => 'Not Acceptable',
                self::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
                self::REQUEST_TIMEOUT => 'Request Timeout',
                self::CONFLICT => 'Conflict',
                self::GONE => 'Gone',
                self::LENGTH_REQUIRED => 'Length Required',
                self::PRECONDITION_FAILED => 'Precondition Failed',
                self::PAYLOAD_TOO_LARGE => 'Payload Too Large',
                self::URI_TOO_LONG => 'URI Too Long',
                self::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
                self::RANGE_NOT_SATISFIABLE => 'Range Not Satisfiable',
                self::EXPECTATION_FAILED => 'Expectation Failed',
                self::IM_A_TEAPOT => "I'm a teapot",
                self::MISDIRECTED_REQUEST => 'Misdirected Request',
                self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
                self::LOCKED => 'Locked',
                self::FAILED_DEPENDENCY => 'Failed Dependency',
                self::TOO_EARLY => 'Too Early',
                self::UPGRADE_REQUIRED => 'Upgrade Required',
                self::PRECONDITION_REQUIRED => 'Precondition Required',
                self::TOO_MANY_REQUESTS => 'Too Many Requests',
                self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
                self::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
                self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
                self::NOT_IMPLEMENTED => 'Not Implemented',
                self::BAD_GATEWAY => 'Bad Gateway',
                self::SERVICE_UNAVAILABLE => 'Service Unavailable',
                self::GATEWAY_TIMEOUT => 'Gateway Timeout',
                self::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
                self::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
                self::INSUFFICIENT_STORAGE => 'Insufficient Storage',
                self::LOOP_DETECTED => 'Loop Detected',
                self::NOT_EXTENDED => 'Not Extended',
                self::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
            };
        }

        /**
         * Determines if the response code is informational (1xx).
         *
         * @return bool True if the response code is informational, false otherwise.
         */
        public function isInformational(): bool
        {
            return $this->value >= 100 && $this->value < 200;
        }

        /**
         * Determines if the response code is a success (2xx).
         *
         * @return bool True if the response code is a success, false otherwise.
         */
        public function isSuccess(): bool
        {
            return $this->value >= 200 && $this->value < 300;
        }

        /**
         * Determines if the response code is a redirection (3xx).
         *
         * @return bool True if the response code is a redirection, false otherwise.
         */
        public function isRedirection(): bool
        {
            return $this->value >= 300 && $this->value < 400;
        }

        /**
         * Determines if the response code is a client error (4xx).
         *
         * @return bool True if the response code is a client error, false otherwise.
         */
        public function isClientError(): bool
        {
            return $this->value >= 400 && $this->value < 500;
        }

        /**
         * Determines if the response code is a server error (5xx).
         *
         * @return bool True if the response code is a server error, false otherwise.
         */
        public function isServerError(): bool
        {
            return $this->value >= 500 && $this->value < 600;
        }

        /**
         * Returns the string representation of the response code, which is its integer value as a string.
         *
         * @return string The string representation of the response code.
         */
        public function toString(): string
        {
            return (string)$this->value;
        }
    }
