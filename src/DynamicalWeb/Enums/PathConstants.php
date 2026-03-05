<?php

    namespace DynamicalWeb\Enums;

    /**
     * Class PathConstants
     * Centralized constants for package paths and resource prefixes
     *
     * @package DynamicalWeb\Classes
     */
    enum PathConstants : string
    {
        /**
         * The built-in package name for DynamicalWeb
         */
        case DYNAMICAL_WEB = 'net.nosial.dynamicalweb';

        /**
         * The resources directory within the package
         */
        case DYNAMICAL_WEB_RESOURCES = 'DynamicalWeb/DynamicalWeb/WebResources';

        /**
         * The built-in pages directory within the package
         */
        case DYNAMICAL_PAGES = 'DynamicalWeb/DynamicalWeb/BuiltinPages';
    }
