<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class Router implements SerializableInterface
    {
        private string $baseUrl;
        private string $basePath;
        private array $responseHandlers;
        /**
         * @var array<string, Route>
         */
        private array $routes;

        public function __construct(array $data)
        {
            $this->baseUrl = $data['base_url'];
            $this->basePath = $data['base_path'];
            $this->responseHandlers = $data['response_handlers'];

            foreach($data['routes'] as $routeData)
            {
                $route = new Route($routeData);
                $this->routes[$route->getPath()] = $route;
            }
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
        public static function fromArray(array $array): SerializableInterface
        {
            return new Router($array);
        }
    }