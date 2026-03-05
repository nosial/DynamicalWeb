<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum Browser: string implements StringInterface
    {
        case CHROME = 'Chrome';
        case FIREFOX = 'Firefox';
        case SAFARI = 'Safari';
        case EDGE = 'Edge';
        case EDGE_LEGACY = 'Edge Legacy';
        case OPERA = 'Opera';
        case OPERA_MINI = 'Opera Mini';
        case OPERA_TOUCH = 'Opera Touch';
        case BRAVE = 'Brave';
        case VIVALDI = 'Vivaldi';
        case INTERNET_EXPLORER = 'Internet Explorer';
        case SAMSUNG_BROWSER = 'Samsung Browser';
        case UC_BROWSER = 'UC Browser';
        case YANDEX_BROWSER = 'Yandex Browser';
        case MAXTHON = 'Maxthon';
        case PUFFIN = 'Puffin';
        case SILK = 'Amazon Silk';
        case CHROMIUM = 'Chromium';
        case FIREFOX_FOCUS = 'Firefox Focus';
        case FIREFOX_REALITY = 'Firefox Reality';
        case DUCKDUCKGO = 'DuckDuckGo';
        case MI_BROWSER = 'Mi Browser';
        case WHALE = 'Whale Browser';
        case QIHOO_360 = '360 Browser';
        case SOGOU = 'Sogou Browser';
        case QQ_BROWSER = 'QQ Browser';
        case BAIDU_BROWSER = 'Baidu Browser';
        case NETSCAPE = 'Netscape';
        case LYNX = 'Lynx';
        case SEAMONKEY = 'SeaMonkey';
        case KONQUEROR = 'Konqueror';
        case PALE_MOON = 'Pale Moon';
        case UNKNOWN = 'Unknown';

        /**
         * Detects the browser from a user agent string
         *
         * @param string $ua The user agent string
         * @param string|null &$version Reference to store the detected version
         * @return self The detected browser
         */
        public static function fromUserAgent(string $ua, ?string &$version = null): self
        {
            // Edge (Chromium-based) - check before Chrome
            if (preg_match('/Edg(?:iOS|A)?\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::EDGE;
            }
            
            // Edge (Legacy)
            if (preg_match('/Edge\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::EDGE_LEGACY;
            }
            
            // DuckDuckGo - check before Chrome
            if (preg_match('/DuckDuckGo\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::DUCKDUCKGO;
            }
            
            // Yandex Browser
            if (preg_match('/YaBrowser\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::YANDEX_BROWSER;
            }
            
            // Chrome and Chrome-based browsers
            if (preg_match('/Chrome\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                
                // Opera variants
                if (preg_match('/OPR\/(\d+(?:\.\d+)*)/i', $ua, $opr))
                {
                    $version = $opr[1];
                    return self::OPERA;
                }
                
                if (preg_match('/OPT\/(\d+(?:\.\d+)*)/i', $ua, $opt))
                {
                    $version = $opt[1];
                    return self::OPERA_TOUCH;
                }
                
                // Brave
                if (preg_match('/Brave[/ ](\d+(?:\.\d+)*)/i', $ua, $brave))
                {
                    $version = $brave[1];
                    return self::BRAVE;
                }
                
                // Vivaldi
                if (preg_match('/Vivaldi\/(\d+(?:\.\d+)*)/i', $ua, $vivaldi))
                {
                    $version = $vivaldi[1];
                    return self::VIVALDI;
                }
                
                // Samsung Browser
                if (preg_match('/SamsungBrowser\/(\d+(?:\.\d+)*)/i', $ua, $samsung))
                {
                    $version = $samsung[1];
                    return self::SAMSUNG_BROWSER;
                }
                
                // UC Browser
                if (preg_match('/UC?Browser\/(\d+(?:\.\d+)*)/i', $ua, $uc))
                {
                    $version = $uc[1];
                    return self::UC_BROWSER;
                }
                
                // Mi Browser
                if (preg_match('/MiuiBrowser\/(\d+(?:\.\d+)*)/i', $ua, $mi))
                {
                    $version = $mi[1];
                    return self::MI_BROWSER;
                }
                
                // Whale Browser
                if (preg_match('/Whale\/(\d+(?:\.\d+)*)/i', $ua, $whale))
                {
                    $version = $whale[1];
                    return self::WHALE;
                }
                
                // 360 Browser
                if (preg_match('/360(?:SE|EE)\/(\d+(?:\.\d+)*)/i', $ua, $qihoo))
                {
                    $version = $qihoo[1];
                    return self::QIHOO_360;
                }
                
                // Sogou Browser
                if (preg_match('/(?:MetaSr|SE )\d+\.\d+/i', $ua))
                {
                    return self::SOGOU;
                }
                
                // QQ Browser
                if (preg_match('/QQBrowser\/(\d+(?:\.\d+)*)/i', $ua, $qq))
                {
                    $version = $qq[1];
                    return self::QQ_BROWSER;
                }
                
                // Baidu Browser
                if (preg_match('/(?:BIDUBrowser|baidubrowser)\/(\d+(?:\.\d+)*)/i', $ua, $baidu))
                {
                    $version = $baidu[1];
                    return self::BAIDU_BROWSER;
                }
                
                // Maxthon
                if (preg_match('/Maxthon\/(\d+(?:\.\d+)*)/i', $ua, $maxthon))
                {
                    $version = $maxthon[1];
                    return self::MAXTHON;
                }
                
                // Puffin
                if (preg_match('/Puffin\/(\d+(?:\.\d+)*)/i', $ua, $puffin))
                {
                    $version = $puffin[1];
                    return self::PUFFIN;
                }
                
                // Amazon Silk
                if (preg_match('/Silk(?:-Accelerated)?\/(\d+(?:\.\d+)*)/i', $ua, $silk))
                {
                    $version = $silk[1];
                    return self::SILK;
                }
                
                // Chromium
                if (preg_match('/Chromium\/(\d+(?:\.\d+)*)/i', $ua, $chromium))
                {
                    $version = $chromium[1];
                    return self::CHROMIUM;
                }
                
                return self::CHROME;
            }
            
            // Safari (must check after Chrome since Chrome UA contains Safari)
            if (preg_match('/Safari\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                if (!preg_match('/Chrome|Chromium|CriOS/i', $ua))
                {
                    if (preg_match('/Version\/(\d+(?:\.\d+)*)/i', $ua, $versionMatch))
                    {
                        $version = $versionMatch[1];
                    }
                    else
                    {
                        $version = $matches[1];
                    }
                    return self::SAFARI;
                }
            }
            
            // Firefox variants
            if (preg_match('/Firefox\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                
                if (preg_match('/Focus/i', $ua))
                {
                    return self::FIREFOX_FOCUS;
                }
                
                if (preg_match('/Mobile VR/i', $ua))
                {
                    return self::FIREFOX_REALITY;
                }
                
                return self::FIREFOX;
            }
            
            // Pale Moon
            if (preg_match('/PaleMoon\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::PALE_MOON;
            }
            
            // SeaMonkey
            if (preg_match('/SeaMonkey\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::SEAMONKEY;
            }
            
            // Internet Explorer
            if (preg_match('/MSIE (\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::INTERNET_EXPLORER;
            }
            
            if (preg_match('/Trident.*rv:(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::INTERNET_EXPLORER;
            }
            
            // Opera (old versions and Opera Mini)
            if (preg_match('/Opera Mini\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::OPERA_MINI;
            }
            
            if (preg_match('/Opera\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::OPERA;
            }
            
            // Konqueror
            if (preg_match('/Konqueror\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::KONQUEROR;
            }
            
            // Netscape
            if (preg_match('/Netscape\/?(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1] ?? null;
                return self::NETSCAPE;
            }
            
            // Lynx
            if (preg_match('/Lynx\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $version = $matches[1];
                return self::LYNX;
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

