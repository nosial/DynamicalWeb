<?php

    namespace DynamicalWeb\Enums\UserAgent;

    use DynamicalWeb\Interfaces\StringInterface;

    enum DeviceBrand: string implements StringInterface
    {
        case APPLE = 'Apple';
        case SAMSUNG = 'Samsung';
        case HUAWEI = 'Huawei';
        case XIAOMI = 'Xiaomi';
        case ONEPLUS = 'OnePlus';
        case GOOGLE = 'Google';
        case LG = 'LG';
        case SONY = 'Sony';
        case HTC = 'HTC';
        case MOTOROLA = 'Motorola';
        case NOKIA = 'Nokia';
        case OPPO = 'Oppo';
        case VIVO = 'Vivo';
        case ASUS = 'Asus';
        case LENOVO = 'Lenovo';
        case REALME = 'Realme';
        case HONOR = 'Honor';
        case TECNO = 'Tecno';
        case INFINIX = 'Infinix';
        case ZTE = 'ZTE';
        case MEIZU = 'Meizu';
        case ALCATEL = 'Alcatel';
        case BLACKBERRY = 'BlackBerry';
        case MICROSOFT = 'Microsoft';
        case AMAZON = 'Amazon';
        case DELL = 'Dell';
        case HP = 'HP';
        case ACER = 'Acer';
        case TOSHIBA = 'Toshiba';
        case SHARP = 'Sharp';
        case PANASONIC = 'Panasonic';
        case KYOCERA = 'Kyocera';
        case TCL = 'TCL';
        case UNKNOWN = 'Unknown';

        /**
         * Returns the patterns to match for this device brand
         *
         * @return array Array of patterns to search for
         */
        public function getPatterns(): array
        {
            return match($this)
            {
                self::SAMSUNG => ['Samsung', 'SM-', 'GT-', 'SCH-', 'SGH-', 'SPH-', 'SAMSUNG'],
                self::HUAWEI => ['Huawei', 'HUAWEI', 'HW-'],
                self::HONOR => ['Honor', 'HONOR', 'STK-', 'YAL-', 'RKY-'],
                self::XIAOMI => ['Xiaomi', 'Mi ', 'Redmi', 'POCO', 'M20', 'M19'],
                self::ONEPLUS => ['OnePlus', 'ONEPLUS', 'OP'],
                self::GOOGLE => ['Pixel', 'Nexus'],
                self::LG => ['LG-', 'LG ', 'LGE'],
                self::SONY => ['Sony', 'SOV', 'SO-'],
                self::HTC => ['HTC', 'Sprint APA'],
                self::MOTOROLA => ['Motorola', 'Moto', 'MOT-', 'XT'],
                self::NOKIA => ['Nokia', 'NOKIA', 'TA-', 'Lumia'],
                self::OPPO => ['OPPO', 'CPH'],
                self::VIVO => ['vivo', 'V20', 'V19', 'V15'],
                self::ASUS => ['ASUS', 'ZenFone'],
                self::LENOVO => ['Lenovo', 'IdeaPad', 'ThinkPad'],
                self::REALME => ['Realme', 'RMX'],
                self::TECNO => ['TECNO', 'Tecno'],
                self::INFINIX => ['Infinix', 'INFINIX'],
                self::ZTE => ['ZTE', 'Z839'],
                self::MEIZU => ['Meizu', 'M9'],
                self::ALCATEL => ['Alcatel', 'ALCATEL'],
                self::BLACKBERRY => ['BlackBerry', 'BB10'],
                self::MICROSOFT => ['Lumia', 'RM-'],
                self::AMAZON => ['KFAPWI', 'KFTHWI', 'Kindle', 'Silk'],
                self::DELL => ['Dell', 'Venue'],
                self::HP => ['HP ', 'HP-'],
                self::ACER => ['Acer', 'A501'],
                self::TOSHIBA => ['Toshiba'],
                self::SHARP => ['SH-', 'Sharp', 'SHARP'],
                self::PANASONIC => ['Panasonic'],
                self::KYOCERA => ['Kyocera'],
                self::TCL => ['TCL'],
                self::APPLE => ['iPhone', 'iPad', 'iPod'],
                self::UNKNOWN => [],
            };
        }

        /**
         * Detects the device brand from a user agent string (Android devices)
         *
         * @param string $ua The user agent string
         * @param string|null &$model Reference to store the detected device model
         * @return self|null The detected device brand or null if not detected
         */
        public static function fromUserAgent(string $ua, ?string &$model = null): ?self
        {
            foreach (self::cases() as $brand)
            {
                if ($brand === self::UNKNOWN || $brand === self::APPLE)
                {
                    continue;
                }

                foreach ($brand->getPatterns() as $pattern)
                {
                    if (stripos($ua, $pattern) !== false)
                    {
                        // Try to extract model - match pattern followed by model name until delimiter
                        if (preg_match('/' . preg_quote($pattern, '/') . '\s+([A-Za-z0-9][A-Za-z0-9\s\+\-]*?)(?=[;)]|$)/i', $ua, $matches))
                        {
                            $model = trim($pattern . ' ' . trim($matches[1]));
                        }
                        elseif (preg_match('/' . preg_quote($pattern, '/') . '([^\s;)]+)/i', $ua, $matches))
                        {
                            $model = trim($matches[0]);
                        }
                        else
                        {
                            $model = $pattern;
                        }
                        return $brand;
                    }
                }
            }

            $model = null;
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

