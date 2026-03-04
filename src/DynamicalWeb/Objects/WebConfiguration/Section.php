<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class Section implements SerializableInterface
    {
        private string $name;
        private string $module;
        private ?string $localeId;

        /**
         * Section Constructor
         *
         * @param string $name The name of the section (e.g., "home", "about", etc.)
         * @param array $data The section data containing 'module' and optional 'locale_id'
         */
        public function __construct(string $name, array $data)
        {
            $this->name = $name;
            $this->module = $data['module'];
            $this->localeId = $data['locale_id'] ?? null;
        }

        /**
         * Returns the name of the section
         *
         * @return string The name of the section
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Returns the module associated with the section
         *
         * @return string The module name
         */
        public function getModule(): string
        {
            return $this->module;
        }

        /**
         * Returns the locale ID associated with the section, if any
         *
         * @return string|null The locale ID or null if not set
         */
        public function getLocaleId(): ?string
        {
            return $this->localeId;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'module'    => $this->module,
                'locale_id' => $this->localeId,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): Section
        {
            // Name must be provided externally; use empty string as placeholder for deserialization
            return new Section($array['name'] ?? '', $array);
        }
    }
