<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum Bot: string implements StringInterface
    {
        case GOOGLEBOT = 'Googlebot';
        case BINGBOT = 'Bingbot';
        case YAHOO_SLURP = 'Yahoo Slurp';
        case DUCKDUCKBOT = 'DuckDuckBot';
        case BAIDU_SPIDER = 'Baidu Spider';
        case YANDEX_BOT = 'Yandex Bot';
        case SOGOU_SPIDER = 'Sogou Spider';
        case EXABOT = 'Exabot';
        case FACEBOOK_BOT = 'Facebook Bot';
        case ALEXA_CRAWLER = 'Alexa Crawler';
        case APPLE_BOT = 'Apple Bot';
        case AHREFS_BOT = 'Ahrefs Bot';
        case SEMRUSH_BOT = 'Semrush Bot';
        case DOTBOT = 'DotBot';
        case MAJESTIC_BOT = 'Majestic Bot';
        case BLEXBOT = 'BLEXBot';
        case PETAL_BOT = 'Petal Bot';
        case DATAFORSEO_BOT = 'DataForSeo Bot';
        case GENERIC_BOT = 'Generic Bot';
        case GENERIC_CRAWLER = 'Generic Crawler';
        case GENERIC_SPIDER = 'Generic Spider';

        /**
         * Returns the pattern to match for this bot in a user agent string
         *
         * @return string The pattern to search for
         */
        public function getPattern(): string
        {
            return match($this) {
                self::GOOGLEBOT => 'Googlebot',
                self::BINGBOT => 'Bingbot',
                self::YAHOO_SLURP => 'Slurp',
                self::DUCKDUCKBOT => 'DuckDuckBot',
                self::BAIDU_SPIDER => 'Baiduspider',
                self::YANDEX_BOT => 'YandexBot',
                self::SOGOU_SPIDER => 'Sogou',
                self::EXABOT => 'Exabot',
                self::FACEBOOK_BOT => 'facebot',
                self::ALEXA_CRAWLER => 'ia_archiver',
                self::APPLE_BOT => 'Applebot',
                self::AHREFS_BOT => 'AhrefsBot',
                self::SEMRUSH_BOT => 'SemrushBot',
                self::DOTBOT => 'DotBot',
                self::MAJESTIC_BOT => 'MJ12bot',
                self::BLEXBOT => 'BLEXBot',
                self::PETAL_BOT => 'PetalBot',
                self::DATAFORSEO_BOT => 'DataForSeoBot',
                self::GENERIC_BOT => 'bot',
                self::GENERIC_CRAWLER => 'crawler',
                self::GENERIC_SPIDER => 'spider',
            };
        }

        /**
         * Detects if the user agent string is a bot and returns the bot type
         *
         * @param string $ua The user agent string
         * @return self|null The detected bot or null if not a bot
         */
        public static function fromUserAgent(string $ua): ?self
        {
            // Check specific bots first
            foreach (self::cases() as $bot)
            {
                if ($bot === self::GENERIC_BOT || $bot === self::GENERIC_CRAWLER || $bot === self::GENERIC_SPIDER)
                {
                    continue;
                }
                
                if (stripos($ua, $bot->getPattern()) !== false)
                {
                    return $bot;
                }
            }

            // Check for generic bot patterns
            if (preg_match('/\b(bot|crawler|spider)\b/i', $ua))
            {
                return self::GENERIC_BOT;
            }

            return null;
        }

        /**
         * @inheritDoc
         */
        public function toString(): string
        {
            return $this->value;
        }
    }

