<?php

    namespace DynamicalWeb\Enums;

    use DynamicalWeb\Interfaces\StringInterface;

    enum XssProtectionLevel : int implements StringInterface
    {
        case DISABLED = 0;
        case LOW = 1;
        case MEDIUM = 2;
        case HIGH = 3;

        /**
         * Returns the appropriate headers for the XSS protection level
         *
         * @param string|null $nonce An optional nonce value for the HIGH protection level
         * @return array An associative array of headers to be applied for the XSS protection level
         */
        public function getHeaders(?string $nonce = null): array
        {
            return match ($this)
            {
                self::DISABLED => [],
                self::LOW => [
                    'X-XSS-Protection' => '1; mode=block',
                ],

                self::MEDIUM => [
                    'Content-Security-Policy' => "default-src 'self'; report-uri /csp-report",
                ],

                self::HIGH => [
                    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; report-uri /csp-report",
                ],
            };
        }

        /**
         * Returns a human-readable string representation of the XSS protection level
         *
         * @return string A string representation of the XSS protection level
         */
        public function toString(): string
        {
            return match ($this)
            {
                self::DISABLED => 'Disabled',
                self::LOW => 'Low',
                self::MEDIUM => 'Medium',
                self::HIGH => 'High',
            };
        }

    }
