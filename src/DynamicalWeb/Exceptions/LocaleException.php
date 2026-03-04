<?php

    namespace DynamicalWeb\Exceptions;

    use Throwable;

    class LocaleException extends DynamicalWebException
    {
        /**
         * @inheritDoc
         */
        public function __construct(string $message="", int $code=0, ?Throwable $previous=null)
        {
            parent::__construct($message, $code, $previous);
        }
    }