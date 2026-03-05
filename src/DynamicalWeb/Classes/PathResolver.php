<?php

    namespace DynamicalWeb\Classes;

    /**
     * Class PathResolver
     * Utility class for path normalization, sanitization, and NCC path construction
     *
     * @package DynamicalWeb\Classes
     */
    class PathResolver
    {
        /**
         * Sanitizes a path by removing directory traversal attempts
         *
         * @param string $path The path to sanitize
         * @return string The sanitized path
         */
        public static function sanitizePath(string $path): string
        {
            return str_replace(['../', '..\\'], '', $path);
        }

        /**
         * Normalizes a path by converting slashes to the system directory separator
         *
         * @param string $path The path to normalize
         * @return string The normalized path
         */
        public static function normalizePath(string $path): string
        {
            return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        }

        /**
         * Builds an NCC protocol path
         *
         * @param string $package The package name
         * @param string $rootPath The root path within the package
         * @param string $modulePath The module or file path
         * @return string The constructed NCC path
         */
        public static function buildNccPath(string $package, string $rootPath, string $modulePath): string
        {
            return sprintf("ncc://%s/%s/%s", $package, $rootPath, $modulePath);
        }

        /**
         * Validates if a file path exists and is a regular file
         *
         * @param string $filePath The file path to validate
         * @return bool True if the file exists and is a regular file
         */
        public static function isValidFile(string $filePath): bool
        {
            return file_exists($filePath) && is_file($filePath);
        }
    }
