<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Interfaces\SerializableInterface;
    use InvalidArgumentException;

    /**
     * A pre-request or post-request module and the routes it runs for.
     *
     * In the configuration an entry is either a module path, which runs for every route, or a mapping:
     *
     *   pre_request:
     *     - "pre_processors/server.phtml"
     *     - module: "pre_processors/authentication.phtml"
     *       except: [configuration_error, logout]   # route IDs to skip
     *       websocket: false                        # skip WebSocket requests
     *
     * `only` lists the route IDs the module runs for; `except` lists route IDs it is skipped for.
     */
    class RequestHook implements SerializableInterface
    {
        private string $module;
        /** @var string[]|null */
        private ?array $only;
        /** @var string[] */
        private array $except;
        private bool $websocket;

        /**
         * RequestHook Constructor
         *
         * @param string|array $data A module path, or an array containing 'module' and optional 'only', 'except'
         *                           and 'websocket'
         * @throws InvalidArgumentException If the entry has no module path
         */
        public function __construct(string|array $data)
        {
            if (is_string($data))
            {
                $data = ['module' => $data];
            }

            if (!isset($data['module']) || !is_string($data['module']) || $data['module'] === '')
            {
                throw new InvalidArgumentException('A pre_request/post_request entry must be a module path or contain a "module" path');
            }

            $this->module = $data['module'];
            $this->only = isset($data['only']) ? array_map('strval', (array)$data['only']) : null;
            $this->except = isset($data['except']) ? array_map('strval', (array)$data['except']) : [];
            $this->websocket = (bool)($data['websocket'] ?? true);
        }

        /**
         * Returns the module path, relative to the application root
         *
         * @return string The module path
         */
        public function getModule(): string
        {
            return $this->module;
        }

        /**
         * Returns the route IDs this module is limited to, or null when it runs for every route
         *
         * @return string[]|null The route IDs, or null
         */
        public function getOnly(): ?array
        {
            return $this->only;
        }

        /**
         * Returns the route IDs this module is skipped for
         *
         * @return string[] The route IDs
         */
        public function getExcept(): array
        {
            return $this->except;
        }

        /**
         * Returns true if this module runs for WebSocket requests
         *
         * @return bool True if it runs for WebSocket requests
         */
        public function runsForWebSocket(): bool
        {
            return $this->websocket;
        }

        /**
         * Determines whether this module runs for the given route.
         *
         * @param Route|null $route The matched route
         * @param bool $isWebSocket Whether the current request is a WebSocket request
         * @return bool True if the module should run
         */
        public function appliesTo(?Route $route, bool $isWebSocket=false): bool
        {
            if ($isWebSocket && !$this->websocket)
            {
                return false;
            }

            $routeId = $route?->getId();
            if ($this->only !== null && ($routeId === null || !in_array($routeId, $this->only, true)))
            {
                return false;
            }

            return $routeId === null || !in_array($routeId, $this->except, true);
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $output = ['module' => $this->module];
            if ($this->only !== null)
            {
                $output['only'] = $this->only;
            }
            if (count($this->except) > 0)
            {
                $output['except'] = $this->except;
            }
            if (!$this->websocket)
            {
                $output['websocket'] = false;
            }

            return $output;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): RequestHook
        {
            return new self($array);
        }
    }
