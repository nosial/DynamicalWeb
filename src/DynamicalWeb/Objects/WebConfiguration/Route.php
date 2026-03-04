<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class Route implements SerializableInterface
    {
        private string $path;
        private string $module;
        private ?string $localeId;
        private ?array $allowedMethods = null;

        /**
         * Route Constructor
         *
         * @param array $data The route data containing 'path', 'module', optional 'locale_id', and optional 'allowed_methods'
         */
        public function __construct(array $data)
        {
            $this->path = $data['path'];
            $this->module = $data['module'];
            $this->localeId = $data['locale_id'] ?? null;

            if(isset($data['allowed_methods']))
            {
                $this->allowedMethods = $data['allowed_methods'];
            }
        }

        /**
         * Returns the path of the route
         *
         * @return string The route path
         */
        public function getPath(): string
        {
            return $this->path;
        }

        /**
         * Returns the module associated with the route
         *
         * @return string The module name
         */
        public function getModule(): string
        {
            return $this->module;
        }

        /**
         * Returns the locale ID associated with the route, if any
         *
         * @return string|null The locale ID or null if not set
         */
        public function getLocaleId(): ?string
        {
            return $this->localeId;
        }

        /**
         * Returns the allowed HTTP methods for the route. If not set, returns ['*'] to indicate all methods are allowed.
         *
         * @return array The allowed HTTP methods
         */
        public function getAllowedMethods(): array
        {
            if($this->allowedMethods === null)
            {
                return ['*'];
            }

            return $this->allowedMethods;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $output = [
                'path' => $this->path,
                'module' => $this->module,
                'locale_id' => $this->localeId
            ];

            if($this->allowedMethods !== null)
            {
                $output['allowed_methods'] = $this->allowedMethods;
            }

            return $output;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): Route
        {
            return new Route($array);
        }
    }