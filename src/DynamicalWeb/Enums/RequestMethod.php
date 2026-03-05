<?php

    namespace DynamicalWeb\Enums;

    use DynamicalWeb\Interfaces\StringInterface;

    enum RequestMethod : string implements StringInterface
    {
        case GET = 'GET';
        case HEAD = 'HEAD';
        case POST = 'POST';
        case PUT = 'PUT';
        case DELETE = 'DELETE';
        case CONNECT = 'CONNECT';
        case OPTIONS = 'OPTIONS';
        case TRACE = 'TRACE';

        /**
         * @inheritDoc
         */
        public function toString(): string
        {
            return $this->value;
        }
    }
