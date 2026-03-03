<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Objects\WebConfiguration\Route;

    class RouteResult
    {
        private ?string $module;
        private ?Route $route;

        /**
         * RouteResult constructor.
         *
         * @param string|null $module The name of the module that matched the route, or null if no module matched.
         * @param Route|null $route The Route object that matched the request, or null if no route matched.
         */
        public function __construct(?string $module, ?Route $route)
        {
            $this->module = $module;
            $this->route = $route;
        }

        /**
         * Returns the name of the module that matched the route, or null if no module matched.
         *
         * @return string|null
         */
        public function getModule(): ?string
        {
            return $this->module;
        }

        /**
         * Returns the Route object that matched the request, or null if no route matched.
         *
         * @return Route|null
         */
        public function getRoute(): ?Route
        {
            return $this->route;
        }
    }
