<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Interfaces\SerializableInterface;

    class RouterConfiguration implements SerializableInterface
    {
        private string $baseUrl;
        private string $basePath;
        private array $responseHandlers;
        /**
         * @var array<string, Route>
         */
        private array $routes;

        /**
         * RouterConfiguration Constructor
         *
         * @param array $data The router configuration data containing 'base_url', 'base_path', optional 'response_handlers', and 'routes'
         */
        public function __construct(array $data)
        {
            $this->baseUrl = $data['base_url'];
            $this->basePath = $data['base_path'];
            $this->responseHandlers = $data['response_handlers'] ?? [];
            $this->routes = [];

            foreach($data['routes'] as $routeData)
            {
                $route = new Route($routeData);
                $this->routes[$route->getPath()] = $route;
            }
        }

        /**
         * Returns the base URL for the router configuration
         *
         * @return string The base URL
         */
        public function getBaseUrl(): string
        {
            return $this->baseUrl;
        }

        /**
         * Returns the base path for the router configuration
         *
         * @return string The base path
         */
        public function getBasePath(): string
        {
            return $this->basePath;
        }

        /**
         * Returns the response handlers mapping for the router configuration
         *
         * @return array<int, string> An associative array mapping HTTP status codes to handler identifiers
         */
        public function getResponseHandlers(): array
        {
            return $this->responseHandlers;
        }

        /**
         * Returns the response handler identifier for a given HTTP status code
         *
         * @param int|ResponseCode $code The HTTP status code or ResponseCode enum value
         * @return string|null The handler identifier associated with the given status code, or null if not defined
         */
        public function getResponseHandler(int|ResponseCode $code): ?string
        {
            if($code instanceof ResponseCode)
            {
                $code = $code->value;
            }

            return $this->responseHandlers[$code] ?? null;
        }

        /**
         * Returns the array of Route objects defined in the router configuration, indexed by their path
         *
         * @return array<string, Route> An associative array mapping route paths to their corresponding Route objects
         */
        public function getRoutes(): array
        {
            return $this->routes;
        }

        /**
         * Returns the Route object associated with a given path
         *
         * @param string $path The path to look up
         * @return Route|null The Route object associated with the given path, or null if not found
         */
        public function getRoute(string $path): ?Route
        {
            return $this->routes[$path] ?? null;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            $output = [
                'base_url' => $this->baseUrl,
                'base_path' => $this->basePath,
                'response_handlers' => $this->responseHandlers,
                'routes' => []
            ];

            foreach($this->routes as $route)
            {
                $output['routes'][$route->getPath()] = $route->toArray();
            }

            return $output;
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): RouterConfiguration
        {
            return new RouterConfiguration($array);
        }
    }