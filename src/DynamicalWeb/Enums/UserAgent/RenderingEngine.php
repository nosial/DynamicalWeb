<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum RenderingEngine: string implements StringInterface
    {
        case WEBKIT = 'WebKit';
        case GECKO = 'Gecko';
        case TRIDENT = 'Trident';
        case EDGE_HTML = 'EdgeHTML';
        case PRESTO = 'Presto';
        case BLINK = 'Blink';
        case UNKNOWN = 'Unknown';

        /**
         * Detects the rendering engine from a user agent string
         *
         * @param string $ua The user agent string
         * @param string|null &$version Reference to store the detected version
         * @return self The detected rendering engine
         */
        public static function fromUserAgent(string $ua, ?string &$version = null): self
        {
            // Blink (Chromium-based browsers) - Chrome, Edge, Opera, Brave, etc.
            // Check for Chrome/Chromium first as they use Blink (forked from WebKit)
            if (preg_match('/Chrome|Chromium|Edg|OPR|Brave|Vivaldi/i', $ua))
            {
                if (preg_match('/AppleWebKit\/(\d+(?:\.\d+)?)/i', $ua, $matches))
                {
                    $version = $matches[1];
                    return self::BLINK;
                }
            }
            
            // WebKit (Safari and older mobile browsers)
            if (preg_match('/AppleWebKit\/(\d+(?:\.\d+)?)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::WEBKIT;
            }
            
            // Gecko (Firefox and derivatives)
            if (preg_match('/Gecko\/(\d+)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::GECKO;
            }
            
            // Trident (Internet Explorer)
            if (preg_match('/Trident\/(\d+(?:\.\d+)?)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::TRIDENT;
            }
            
            // EdgeHTML (Legacy Edge)
            if (preg_match('/EdgeHTML\/(\d+(?:\.\d+)?)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::EDGE_HTML;
            }
            
            // Presto (Old Opera)
            if (preg_match('/Presto\/(\d+(?:\.\d+)?)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::PRESTO;
            }
            
            $version = null;
            return self::UNKNOWN;
        }

        /**
         * @inheritDoc
         */
        public function toString(): string
        {
            return $this->value;
        }
    }

