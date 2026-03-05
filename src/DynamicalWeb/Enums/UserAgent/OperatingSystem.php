<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum OperatingSystem: string implements StringInterface
    {
        case IOS = 'iOS';
        case IPADOS = 'iPadOS';
        case ANDROID = 'Android';
        case WINDOWS = 'Windows';
        case WINDOWS_PHONE = 'Windows Phone';
        case WINDOWS_MOBILE = 'Windows Mobile';
        case MACOS = 'macOS';
        case LINUX = 'Linux';
        case UBUNTU = 'Ubuntu';
        case FEDORA = 'Fedora';
        case DEBIAN = 'Debian';
        case REDHAT = 'Red Hat';
        case CENTOS = 'CentOS';
        case ARCH = 'Arch Linux';
        case MINT = 'Linux Mint';
        case SUSE = 'SUSE';
        case CHROME_OS = 'Chrome OS';
        case FIREFOX_OS = 'Firefox OS';
        case BLACKBERRY_OS = 'BlackBerry OS';
        case SYMBIAN = 'Symbian';
        case WEBOS = 'webOS';
        case TIZEN = 'Tizen';
        case SAILFISH = 'Sailfish OS';
        case KAIOS = 'KaiOS';
        case HARMONY_OS = 'HarmonyOS';
        case WATCHOS = 'watchOS';
        case TVOS = 'tvOS';
        case FREEBSD = 'FreeBSD';
        case OPENBSD = 'OpenBSD';
        case NETBSD = 'NetBSD';
        case SOLARIS = 'Solaris';
        case AIX = 'AIX';
        case UNKNOWN = 'Unknown';

        /**
         * Detects the operating system from a user agent string
         *
         * @param string $ua The user agent string
         * @param string|null &$version Reference to store the detected version
         * @param string|null &$platform Reference to store the platform name
         * @return self The detected operating system
         */
        public static function fromUserAgent(string $ua, ?string &$version = null, ?string &$platform = null): self
        {
            // iOS devices
            if (preg_match('/\(iPhone;/i', $ua))
            {
                $platform = 'iOS';
                if (preg_match('/OS (\d+)[_.](\d+)(?:[_.](\d+))?/i', $ua, $matches))
                {
                    $version = $matches[1] . '.' . $matches[2] . (isset($matches[3]) ? '.' . $matches[3] : '');
                }
                return self::IOS;
            }
            
            if (preg_match('/\(iPad;/i', $ua))
            {
                $platform = 'iOS';
                if (preg_match('/OS (\d+)[_.](\d+)(?:[_.](\d+))?/i', $ua, $matches))
                {
                    $version = $matches[1] . '.' . $matches[2] . (isset($matches[3]) ? '.' . $matches[3] : '');
                }
                return self::IPADOS;
            }
            
            if (preg_match('/\(iPod;/i', $ua))
            {
                $platform = 'iOS';
                if (preg_match('/OS (\d+)[_.](\d+)(?:[_.](\d+))?/i', $ua, $matches))
                {
                    $version = $matches[1] . '.' . $matches[2] . (isset($matches[3]) ? '.' . $matches[3] : '');
                }
                return self::IOS;
            }
            
            // watchOS
            if (preg_match('/watchOS\/(\d+(?:\.\d+)*)/i', $ua, $matches))
            {
                $platform = 'watchOS';
                $version = $matches[1];
                return self::WATCHOS;
            }
            
            // tvOS
            if (preg_match('/AppleTV|tvOS/i', $ua))
            {
                $platform = 'tvOS';
                if (preg_match('/(?:tvOS|CPU OS) (\d+(?:[_.]\d+)*)/i', $ua, $matches))
                {
                    $version = str_replace('_', '.', $matches[1]);
                }
                return self::TVOS;
            }
            
            // Android
            if (preg_match('/Android/i', $ua))
            {
                $platform = 'Android';
                if (preg_match('/Android[\s\/](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::ANDROID;
            }
            
            // HarmonyOS
            if (preg_match('/HarmonyOS/i', $ua))
            {
                $platform = 'HarmonyOS';
                if (preg_match('/HarmonyOS[\s\/](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::HARMONY_OS;
            }
            
            // Windows variants
            if (preg_match('/Windows/i', $ua))
            {
                $platform = 'Windows';
                
                // Windows Phone
                if (preg_match('/Windows Phone/i', $ua))
                {
                    if (preg_match('/Windows Phone (?:OS )?(\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::WINDOWS_PHONE;
                }
                
                // Windows Mobile
                if (preg_match('/Windows Mobile|WinCE|PocketPC/i', $ua))
                {
                    return self::WINDOWS_MOBILE;
                }
                
                // Windows NT
                if (preg_match('/Windows NT (\d+\.\d+)/i', $ua, $matches))
                {
                    $version = self::mapWindowsVersion($matches[1]);
                }
                return self::WINDOWS;
            }
            
            // macOS
            if (preg_match('/Mac OS X/i', $ua))
            {
                $platform = 'macOS';
                if (preg_match('/Mac OS X (\d+)[_.](\d+)(?:[_.](\d+))?/i', $ua, $matches))
                {
                    $version = $matches[1] . '.' . $matches[2] . (isset($matches[3]) ? '.' . $matches[3] : '');
                }
                return self::MACOS;
            }
            
            // Chrome OS
            if (preg_match('/CrOS/i', $ua))
            {
                $platform = 'Chrome OS';
                if (preg_match('/CrOS \S+ (\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::CHROME_OS;
            }
            
            // Tizen
            if (preg_match('/Tizen/i', $ua))
            {
                $platform = 'Tizen';
                if (preg_match('/Tizen[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::TIZEN;
            }
            
            // KaiOS
            if (preg_match('/KaiOS/i', $ua))
            {
                $platform = 'KaiOS';
                if (preg_match('/KaiOS[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::KAIOS;
            }
            
            // Firefox OS
            if (preg_match('/(?:Mobile|Tablet);.*Firefox/i', $ua) && !preg_match('/Android|iOS|Windows/i', $ua))
            {
                $platform = 'Firefox OS';
                return self::FIREFOX_OS;
            }
            
            // webOS
            if (preg_match('/webOS/i', $ua))
            {
                $platform = 'webOS';
                if (preg_match('/webOS[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::WEBOS;
            }
            
            // Sailfish OS
            if (preg_match('/Sailfish/i', $ua))
            {
                $platform = 'Sailfish OS';
                if (preg_match('/Sailfish[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::SAILFISH;
            }
            
            // BlackBerry
            if (preg_match('/BlackBerry|BB10|PlayBook/i', $ua))
            {
                $platform = 'BlackBerry';
                if (preg_match('/(?:Version|BlackBerry\w+)\/(\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::BLACKBERRY_OS;
            }
            
            // Symbian
            if (preg_match('/Symbian|SymbianOS|Series60|S60/i', $ua))
            {
                $platform = 'Symbian';
                if (preg_match('/SymbianOS\/(\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::SYMBIAN;
            }
            
            // BSD variants
            if (preg_match('/FreeBSD/i', $ua))
            {
                $platform = 'FreeBSD';
                if (preg_match('/FreeBSD[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::FREEBSD;
            }
            
            if (preg_match('/OpenBSD/i', $ua))
            {
                $platform = 'OpenBSD';
                if (preg_match('/OpenBSD[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::OPENBSD;
            }
            
            if (preg_match('/NetBSD/i', $ua))
            {
                $platform = 'NetBSD';
                if (preg_match('/NetBSD[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::NETBSD;
            }
            
            // Unix variants
            if (preg_match('/SunOS|Solaris/i', $ua))
            {
                $platform = 'Solaris';
                return self::SOLARIS;
            }
            
            if (preg_match('/AIX/i', $ua))
            {
                $platform = 'AIX';
                if (preg_match('/AIX[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                {
                    $version = $matches[1];
                }
                return self::AIX;
            }
            
            // Linux distributions
            if (preg_match('/Linux/i', $ua))
            {
                $platform = 'Linux';
                
                if (preg_match('/Ubuntu/i', $ua))
                {
                    if (preg_match('/Ubuntu[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::UBUNTU;
                }
                
                if (preg_match('/Fedora/i', $ua))
                {
                    if (preg_match('/Fedora[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::FEDORA;
                }
                
                if (preg_match('/Debian/i', $ua))
                {
                    if (preg_match('/Debian[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::DEBIAN;
                }
                
                if (preg_match('/Red Hat|RHEL/i', $ua))
                {
                    return self::REDHAT;
                }
                
                if (preg_match('/CentOS/i', $ua))
                {
                    if (preg_match('/CentOS[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::CENTOS;
                }
                
                if (preg_match('/Arch/i', $ua))
                {
                    return self::ARCH;
                }
                
                if (preg_match('/(?:Linux )?Mint/i', $ua))
                {
                    if (preg_match('/Mint[\/\s](\d+(?:\.\d+)*)/i', $ua, $matches))
                    {
                        $version = $matches[1];
                    }
                    return self::MINT;
                }
                
                if (preg_match('/SUSE|openSUSE/i', $ua))
                {
                    return self::SUSE;
                }
                
                return self::LINUX;
            }
            
            $version = null;
            $platform = null;
            return self::UNKNOWN;
        }

        /**
         * Maps Windows NT version to friendly name
         *
         * @param string $ntVersion The NT version number
         * @return string The friendly version name
         */
        private static function mapWindowsVersion(string $ntVersion): string
        {
            return match($ntVersion)
            {
                '11.0' => '11',
                '10.0' => '10',
                '6.3' => '8.1',
                '6.2' => '8',
                '6.1' => '7',
                '6.0' => 'Vista',
                '5.2' => 'XP x64',
                '5.1' => 'XP',
                '5.0' => '2000',
                '4.0' => 'NT 4.0',
                default => $ntVersion,
            };
        }

        /**
         * @inheritDoc
         */
        public function toString(): string
        {
            return $this->value;
        }
    }

