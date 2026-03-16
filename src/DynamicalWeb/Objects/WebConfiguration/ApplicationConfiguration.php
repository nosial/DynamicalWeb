<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Enums\XssProtectionLevel;
    use DynamicalWeb\Interfaces\SerializableInterface;

    class ApplicationConfiguration implements SerializableInterface
    {
        private string $name;
        private string $root;
        private string $resources;
        private ?string $defaultLocale;
        private bool $reportErrors;
        private XssProtectionLevel $xssLevel;
        private ?array $preRequest;
        private ?array $postRequest;
        private bool $debugPanel;
        private bool $disableApcu;
        private bool $disableDefaultHeaders;
        private int $staticCacheMaxAge;
        private int $apcuContentMaxSize;
        private int $apcuContentTtl;
        private int $apcuMetaTtl;
        private int $apcuConfigTtl;

        /**
         * ApplicationConfiguration Constructor
         *
         * @param array $data The application configuration data containing 'name', 'root', 'resources',
         *        optional 'default_locale', 'report_errors', optional 'xss_level', optional 'pre_request',
         *        optional 'post_request', optional 'debug_panel', optional 'disable_apcu', optional 'disable_default_headers',
         *        optional 'static_cache_max_age', optional 'apcu_content_max_size', optional 'apcu_content_ttl',
         *        optional 'apcu_meta_ttl', and optional 'apcu_config_ttl'
         */
        public function __construct(array $data)
        {
            $this->name = $data['name'];
            $this->root = $data['root'];
            $this->resources = $data['resources'];
            $this->defaultLocale = $data['default_locale'] ?? null;
            $this->reportErrors = $data['report_errors'];
            $this->xssLevel = XssProtectionLevel::tryFrom($data['xss_level'] ?? 0) ?? XssProtectionLevel::DISABLED;
            $this->preRequest = $data['pre_request'] ?? null;
            $this->postRequest = $data['post_request'] ?? null;
            $this->debugPanel = $data['debug_panel'] ?? false;
            $this->disableApcu = $data['disable_apcu'] ?? false;
            $this->disableDefaultHeaders = $data['disable_default_headers'] ?? false;
            $this->staticCacheMaxAge = $data['static_cache_max_age'] ?? 3600;
            $this->apcuContentMaxSize = $data['apcu_content_max_size'] ?? 262144;
            $this->apcuContentTtl = $data['apcu_content_ttl'] ?? 3600;
            $this->apcuMetaTtl = $data['apcu_meta_ttl'] ?? 10;
            $this->apcuConfigTtl = $data['apcu_config_ttl'] ?? 60;
        }

        /**
         * Returns the name of the application
         *
         * @return string The name of the application
         */
        public function getName(): string
        {
            return $this->name;
        }

        /**
         * Returns the root directory of the application
         *
         * @return string The root directory of the application
         */
        public function getRoot(): string
        {
            return $this->root;
        }

        /**
         * Returns the resources directory of the application
         *
         * @return string The resources directory of the application
         */
        public function getResources(): string
        {
            return $this->resources;
        }

        /**
         * Returns the default locale of the application, if any
         *
         * @return string|null The default locale or null if not set
         */
        public function getDefaultLocale(): ?string
        {
            return $this->defaultLocale;
        }

        /**
         * Returns true if error reporting is enabled for the application, false otherwise
         *
         * @return bool True if error reporting is enabled, false otherwise
         */
        public function errorReportingEnabled(): bool
        {
            return $this->reportErrors;
        }

        /**
         * Returns the XSS protection level for the application
         *
         * @return XssProtectionLevel The XSS protection level for the application
         */
        public function getXssLevel(): XssProtectionLevel
        {
            return $this->xssLevel;
        }

        /**
         * Returns the pre-request handlers for the application, if any
         *
         * @return array|null The pre-request handlers or null if not set
         */
        public function getPreRequest(): ?array
        {
            return $this->preRequest;
        }

        /**
         * Returns the post-request handlers for the application, if any
         *
         * @return array|null The post-request handlers or null if not set
         */
        public function getPostRequest(): ?array
        {
            return $this->postRequest;
        }

        /**
         * Returns true if the debug panel is enabled for the application, false otherwise
         *
         * @return bool True if the debug panel is enabled, false otherwise
         */
        public function isDebugPanelEnabled(): bool
        {
            return $this->debugPanel;
        }

        /**
         * Returns true when APCu caching has been explicitly disabled for this application.
         *
         * @return bool True if APCu caching is disabled, false otherwise
         */
        public function isApcuDisabled(): bool
        {
            return $this->disableApcu;
        }

        /**
         * Returns true when the default framework headers (X-Powered-By, X-Request-ID) should be omitted from responses.
         *
         * @return bool True if default headers are disabled, false otherwise
         */
        public function isDefaultHeadersDisabled(): bool
        {
            return $this->disableDefaultHeaders;
        }

        /**
         * Returns the maximum age in seconds for caching static file responses via the Cache-Control header.
         *
         * @return int The max-age value in seconds (default: 3600)
         */
        public function getStaticCacheMaxAge(): int
        {
            return $this->staticCacheMaxAge;
        }

        /**
         * Returns the maximum file size in bytes eligible for APCu content caching.
         * Files larger than this threshold are streamed directly from disk.
         *
         * @return int The max size in bytes (default: 262144 / 256 KB)
         */
        public function getApcuContentMaxSize(): int
        {
            return $this->apcuContentMaxSize;
        }

        /**
         * Returns the TTL in seconds for cached static file content stored in APCu.
         *
         * @return int The TTL in seconds (default: 3600)
         */
        public function getApcuContentTtl(): int
        {
            return $this->apcuContentTtl;
        }

        /**
         * Returns the TTL in seconds for cached file metadata (mtime, size) stored in APCu.
         *
         * @return int The TTL in seconds (default: 10)
         */
        public function getApcuMetaTtl(): int
        {
            return $this->apcuMetaTtl;
        }

        /**
         * Returns the TTL in seconds for the parsed web configuration cached in APCu.
         *
         * @return int The TTL in seconds (default: 60)
         */
        public function getApcuConfigTtl(): int
        {
            return $this->apcuConfigTtl;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'root' => $this->root,
                'resources' => $this->resources,
                'default_locale' => $this->defaultLocale,
                'report_errors' => $this->reportErrors,
                'xss_level' => $this->xssLevel->value,
                'pre_request' => $this->preRequest,
                'post_request' => $this->postRequest,
                'debug_panel' => $this->debugPanel,
                'disable_apcu' => $this->disableApcu,
                'disable_default_headers' => $this->disableDefaultHeaders,
                'static_cache_max_age' => $this->staticCacheMaxAge,
                'apcu_content_max_size' => $this->apcuContentMaxSize,
                'apcu_content_ttl' => $this->apcuContentTtl,
                'apcu_meta_ttl' => $this->apcuMetaTtl,
                'apcu_config_ttl' => $this->apcuConfigTtl,
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): ApplicationConfiguration
        {
            return new self($array);
        }
    }