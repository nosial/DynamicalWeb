<?php

    namespace DynamicalWeb\Enums;

    /**
     * Language codes following ISO 639-1 (2-letter codes) standard
     * Provides mapping from ISO 639-2/639-3 (3-letter codes) to ISO 639-1
     */
    enum LanguageCode: string
    {
        case ENGLISH = 'en';
        case FRENCH = 'fr';
        case GERMAN = 'de';
        case SPANISH = 'es';
        case ITALIAN = 'it';
        case PORTUGUESE = 'pt';
        case RUSSIAN = 'ru';
        case JAPANESE = 'ja';
        case KOREAN = 'ko';
        case CHINESE = 'zh';
        case ARABIC = 'ar';
        case HINDI = 'hi';
        case DUTCH = 'nl';
        case SWEDISH = 'sv';
        case POLISH = 'pl';
        case TURKISH = 'tr';
        case VIETNAMESE = 'vi';
        case THAI = 'th';
        case CZECH = 'cs';
        case ROMANIAN = 'ro';
        case DANISH = 'da';
        case FINNISH = 'fi';
        case GREEK = 'el';
        case HEBREW = 'he';
        case HUNGARIAN = 'hu';
        case INDONESIAN = 'id';
        case NORWEGIAN = 'no';
        case UKRAINIAN = 'uk';
        case BULGARIAN = 'bg';
        case CROATIAN = 'hr';
        case SLOVAK = 'sk';
        case SLOVENIAN = 'sl';
        case SERBIAN = 'sr';
        case CATALAN = 'ca';
        case LITHUANIAN = 'lt';
        case LATVIAN = 'lv';
        case ESTONIAN = 'et';
        case MALAY = 'ms';
        case PERSIAN = 'fa';
        case URDU = 'ur';
        case BENGALI = 'bn';
        case TAMIL = 'ta';
        case TELUGU = 'te';
        case MARATHI = 'mr';
        case GUJARATI = 'gu';
        case KANNADA = 'kn';
        case MALAYALAM = 'ml';
        case PUNJABI = 'pa';
        case SWAHILI = 'sw';
        case AFRIKAANS = 'af';
        case ALBANIAN = 'sq';
        case ARMENIAN = 'hy';
        case AZERBAIJANI = 'az';
        case BASQUE = 'eu';
        case BELARUSIAN = 'be';
        case BOSNIAN = 'bs';
        case BURMESE = 'my';
        case GALICIAN = 'gl';
        case GEORGIAN = 'ka';
        case ICELANDIC = 'is';
        case KAZAKH = 'kk';
        case KHMER = 'km';
        case LAO = 'lo';
        case MACEDONIAN = 'mk';
        case MALTESE = 'mt';
        case MONGOLIAN = 'mn';
        case NEPALI = 'ne';
        case SINHALA = 'si';
        case TAGALOG = 'tl';
        case UZBEK = 'uz';
        case WELSH = 'cy';
        case YIDDISH = 'yi';

        /**
         * Maps ISO 639-2/639-3 (3-letter) codes to ISO 639-1 (2-letter) codes
         */
        private const ISO_639_2_TO_1_MAP = [
            'eng' => 'en',
            'fra' => 'fr',
            'deu' => 'de',
            'ger' => 'de', // Alternative for German
            'spa' => 'es',
            'ita' => 'it',
            'por' => 'pt',
            'rus' => 'ru',
            'jpn' => 'ja',
            'kor' => 'ko',
            'zho' => 'zh',
            'chi' => 'zh', // Alternative for Chinese
            'ara' => 'ar',
            'hin' => 'hi',
            'nld' => 'nl',
            'dut' => 'nl', // Alternative for Dutch
            'swe' => 'sv',
            'pol' => 'pl',
            'tur' => 'tr',
            'vie' => 'vi',
            'tha' => 'th',
            'ces' => 'cs',
            'cze' => 'cs', // Alternative for Czech
            'ron' => 'ro',
            'rum' => 'ro', // Alternative for Romanian
            'dan' => 'da',
            'fin' => 'fi',
            'ell' => 'el',
            'gre' => 'el', // Alternative for Greek
            'heb' => 'he',
            'hun' => 'hu',
            'ind' => 'id',
            'nor' => 'no',
            'ukr' => 'uk',
            'bul' => 'bg',
            'hrv' => 'hr',
            'slk' => 'sk',
            'slo' => 'sk', // Alternative for Slovak
            'slv' => 'sl',
            'srp' => 'sr',
            'cat' => 'ca',
            'lit' => 'lt',
            'lav' => 'lv',
            'est' => 'et',
            'msa' => 'ms',
            'may' => 'ms', // Alternative for Malay
            'fas' => 'fa',
            'per' => 'fa', // Alternative for Persian
            'urd' => 'ur',
            'ben' => 'bn',
            'tam' => 'ta',
            'tel' => 'te',
            'mar' => 'mr',
            'guj' => 'gu',
            'kan' => 'kn',
            'mal' => 'ml',
            'pan' => 'pa',
            'swa' => 'sw',
            'afr' => 'af',
            'sqi' => 'sq',
            'alb' => 'sq', // Alternative for Albanian
            'hye' => 'hy',
            'arm' => 'hy', // Alternative for Armenian
            'aze' => 'az',
            'eus' => 'eu',
            'baq' => 'eu', // Alternative for Basque
            'bel' => 'be',
            'bos' => 'bs',
            'mya' => 'my',
            'bur' => 'my', // Alternative for Burmese
            'glg' => 'gl',
            'kat' => 'ka',
            'geo' => 'ka', // Alternative for Georgian
            'isl' => 'is',
            'ice' => 'is', // Alternative for Icelandic
            'kaz' => 'kk',
            'khm' => 'km',
            'lao' => 'lo',
            'mkd' => 'mk',
            'mac' => 'mk', // Alternative for Macedonian
            'mlt' => 'mt',
            'mon' => 'mn',
            'nep' => 'ne',
            'sin' => 'si',
            'tgl' => 'tl',
            'uzb' => 'uz',
            'cym' => 'cy',
            'wel' => 'cy', // Alternative for Welsh
            'yid' => 'yi',
        ];

        /**
         * Normalizes a language code to ISO 639-1 format (2-letter code)
         *
         * @param string $code The language code to normalize (can be 2-letter or 3-letter)
         * @return string The normalized ISO 639-1 code (2 letters)
         */
        public static function normalize(string $code): string
        {
            $code = strtolower(trim($code));
            
            // If already 2 letters, return as-is
            if (strlen($code) === 2)
            {
                return $code;
            }

            // Try to map from ISO 639-2/3 to ISO 639-1
            if (isset(self::ISO_639_2_TO_1_MAP[$code]))
            {
                return self::ISO_639_2_TO_1_MAP[$code];
            }

            // Fallback: take first 2 characters
            return substr($code, 0, 2);
        }

        /**
         * Attempts to create a LanguageCode enum from a code string
         *
         * @param string $code The language code (2-letter or 3-letter)
         * @return LanguageCode|null The matching LanguageCode enum case, or null if not found
         */
        public static function fromCode(string $code): ?LanguageCode
        {
            $normalized = self::normalize($code);
            
            return self::tryFrom($normalized);
        }

        /**
         * Gets the ISO 639-2 (3-letter) code for this language
         *
         * @return string The 3-letter ISO 639-2 code
         */
        public function toISO639_2(): string
        {
            // Reverse lookup in the map
            $iso1 = $this->value;
            
            foreach (self::ISO_639_2_TO_1_MAP as $iso2 => $iso1_mapped)
            {
                if ($iso1_mapped === $iso1)
                {
                    return $iso2;
                }
            }
            
            // Fallback: return value as-is
            return $iso1;
        }

        /**
         * Gets the human-readable name of the language
         *
         * @return string The language name
         */
        public function getName(): string
        {
            return match($this) {
                self::ENGLISH => 'English',
                self::FRENCH => 'French',
                self::GERMAN => 'German',
                self::SPANISH => 'Spanish',
                self::ITALIAN => 'Italian',
                self::PORTUGUESE => 'Portuguese',
                self::RUSSIAN => 'Russian',
                self::JAPANESE => 'Japanese',
                self::KOREAN => 'Korean',
                self::CHINESE => 'Chinese',
                self::ARABIC => 'Arabic',
                self::HINDI => 'Hindi',
                self::DUTCH => 'Dutch',
                self::SWEDISH => 'Swedish',
                self::POLISH => 'Polish',
                self::TURKISH => 'Turkish',
                self::VIETNAMESE => 'Vietnamese',
                self::THAI => 'Thai',
                self::CZECH => 'Czech',
                self::ROMANIAN => 'Romanian',
                self::DANISH => 'Danish',
                self::FINNISH => 'Finnish',
                self::GREEK => 'Greek',
                self::HEBREW => 'Hebrew',
                self::HUNGARIAN => 'Hungarian',
                self::INDONESIAN => 'Indonesian',
                self::NORWEGIAN => 'Norwegian',
                self::UKRAINIAN => 'Ukrainian',
                self::BULGARIAN => 'Bulgarian',
                self::CROATIAN => 'Croatian',
                self::SLOVAK => 'Slovak',
                self::SLOVENIAN => 'Slovenian',
                self::SERBIAN => 'Serbian',
                self::CATALAN => 'Catalan',
                self::LITHUANIAN => 'Lithuanian',
                self::LATVIAN => 'Latvian',
                self::ESTONIAN => 'Estonian',
                self::MALAY => 'Malay',
                self::PERSIAN => 'Persian',
                self::URDU => 'Urdu',
                self::BENGALI => 'Bengali',
                self::TAMIL => 'Tamil',
                self::TELUGU => 'Telugu',
                self::MARATHI => 'Marathi',
                self::GUJARATI => 'Gujarati',
                self::KANNADA => 'Kannada',
                self::MALAYALAM => 'Malayalam',
                self::PUNJABI => 'Punjabi',
                self::SWAHILI => 'Swahili',
                self::AFRIKAANS => 'Afrikaans',
                self::ALBANIAN => 'Albanian',
                self::ARMENIAN => 'Armenian',
                self::AZERBAIJANI => 'Azerbaijani',
                self::BASQUE => 'Basque',
                self::BELARUSIAN => 'Belarusian',
                self::BOSNIAN => 'Bosnian',
                self::BURMESE => 'Burmese',
                self::GALICIAN => 'Galician',
                self::GEORGIAN => 'Georgian',
                self::ICELANDIC => 'Icelandic',
                self::KAZAKH => 'Kazakh',
                self::KHMER => 'Khmer',
                self::LAO => 'Lao',
                self::MACEDONIAN => 'Macedonian',
                self::MALTESE => 'Maltese',
                self::MONGOLIAN => 'Mongolian',
                self::NEPALI => 'Nepali',
                self::SINHALA => 'Sinhala',
                self::TAGALOG => 'Tagalog',
                self::UZBEK => 'Uzbek',
                self::WELSH => 'Welsh',
                self::YIDDISH => 'Yiddish',
            };
        }
    }
