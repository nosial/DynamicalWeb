<?php

    namespace DynamicalWeb\Objects;

    class Locale
    {
        private string $localeCode;
        private array $data;

        /**
         * Locale Consturctor
         *
         * @param string $localeCode The locale code that's being loaded
         * @param array $data The locale data array
         */
        public function __construct(string $localeCode, array $data)
        {
            $this->localeCode = $localeCode;
            $this->data = $data;
        }

        /**
         * Returns the ISO 639-1 language code for this locale.
         *
         * @return string The locale code, eg; "en", "fr", "de"
         */
        public function getLocaleCode(): string
        {
            return $this->localeCode;
        }

        /**
         * Returns all locale data for a specific locale ID.
         *
         * @param string $localeId The first-layer locale ID, eg; "home", "about"
         * @return array|null The locale data for the specified ID, or null if not found
         */
        public function getLocaleData(string $localeId): ?array
        {
            return $this->data[$localeId] ?? null;
        }

        /**
         * Returns a specific locale string by ID and key, with optional replacements.
         *
         * @param string $localeId The first-layer locale ID, eg; "home", "about"
         * @param string $key The second-layer key, eg; "welcome_banner", "test"
         * @param array $replacements Optional array of replacements for patterns like {username}
         * @return string|null The locale string with replacements applied, or null if not found
         */
        public function getString(string $localeId, string $key, array $replacements = []): ?string
        {
            $localeData = $this->getLocaleData($localeId);
            if ($localeData === null)
            {
                return null;
            }

            $string = $localeData[$key] ?? null;
            if ($string === null)
            {
                return null;
            }

            // Apply replacements
            if (!empty($replacements))
            {
                foreach ($replacements as $placeholder => $value)
                {
                    $string = str_replace('{' . $placeholder . '}', (string)$value, $string);
                }
            }

            return $string;
        }

        /**
         * Checks if a specific locale ID exists in this locale.
         *
         * @param string $localeId The first-layer locale ID
         * @return bool True if the locale ID exists, false otherwise
         */
        public function hasLocaleId(string $localeId): bool
        {
            return isset($this->data[$localeId]);
        }

        /**
         * Checks if a specific key exists within a locale ID.
         *
         * @param string $localeId The first-layer locale ID
         * @param string $key The second-layer key
         * @return bool True if the key exists within the locale ID, false otherwise
         */
        public function hasKey(string $localeId, string $key): bool
        {
            return isset($this->data[$localeId][$key]);
        }

        /**
         * Returns all available locale IDs in this locale.
         *
         * @return array Array of locale IDs
         */
        public function getLocaleIds(): array
        {
            return array_keys($this->data);
        }
    }
