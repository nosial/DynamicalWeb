<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Interfaces\SerializableInterface;
    use DynamicalWeb\Objects\WebConfiguration\ApplicationConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\RouterConfiguration;
    use DynamicalWeb\Objects\WebConfiguration\Section;

    class WebConfiguration implements SerializableInterface
    {
        private ApplicationConfiguration $application;
        private RouterConfiguration $router;
        private ?array $locales;
        /** @var array<string, Section> */
        private array $sections;

        /**
         * WebConfiguration constructor.
         *
         * @param array $data
         */
        public function __construct(array $data)
        {
            $this->application = new ApplicationConfiguration($data['application']);
            $this->router = new RouterConfiguration($data['router']);
            $this->locales = $data['locales'] ?? null;
            $this->sections = [];

            foreach (($data['sections'] ?? []) as $name => $sectionData)
            {
                $this->sections[$name] = new Section($name, $sectionData);
            }
        }

        /**
         * Returns the application configuration.
         *
         * @return ApplicationConfiguration
         */
        public function getApplication(): ApplicationConfiguration
        {
            return $this->application;
        }

        /**
         * Returns the router configuration.
         *
         * @return RouterConfiguration
         */
        public function getRouter(): RouterConfiguration
        {
            return $this->router;
        }

        /**
         * Returns the list of configured locales, or null if not set.
         *
         * @return array|null
         */
        public function getLocales(): ?array
        {
            return $this->locales;
        }

        /**
         * Returns all configured sections keyed by section name.
         *
         * @return array<string, Section>
         */
        public function getSections(): array
        {
            return $this->sections;
        }

        /**
         * Returns a specific section by name, or null if not found.
         *
         * @param string $name
         * @return Section|null
         */
        public function getSection(string $name): ?Section
        {
            return $this->sections[$name] ?? null;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $sectionsArray = [];
            foreach ($this->sections as $name => $section)
            {
                $sectionsArray[$name] = $section->toArray();
            }

            return [
                'application' => $this->application->toArray(),
                'router' => $this->router->toArray(),
                'locales' => $this->locales,
                'sections' => $sectionsArray,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): WebConfiguration
        {
            return new self($array);
        }
    }