<?php

    namespace DynamicalWeb\Objects\WebConfiguration;

    use DynamicalWeb\Enums\XssProtectionLevel;
    use DynamicalWeb\Interfaces\SerializableInterface;

    class Application implements SerializableInterface
    {
        private string $name;
        private string $root;
        private string $resources;
        private bool $reportErrors;
        private XssProtectionLevel $xssLevel;
        private ?array $preRequest;
        private ?array $postRequest;

        public function __construct(array $data)
        {
            $this->name = $data['name'];
            $this->root = $data['root'];
            $this->resources = $data['resources'];
            $this->reportErrors = $data['report_errors'];
            $this->xssLevel = XssProtectionLevel::tryFrom($data['xss_level'] ?? 0) ?? XssProtectionLevel::DISABLED;
            $this->preRequest = $data['pre_request'] ?? null;
            $this->postRequest = $data['post_request'] ?? null;
        }

        public function getName(): string
        {
            return $this->name;
        }

        public function getRoot(): string
        {
            return $this->root;
        }

        public function getResources(): string
        {
            return $this->resources;
        }

        public function isReportErrors(): bool
        {
            return $this->reportErrors;
        }

        public function getXssLevel(): XssProtectionLevel
        {
            return $this->xssLevel;
        }

        public function getPreRequest(): ?array
        {
            return $this->preRequest;
        }

        public function getPostRequest(): ?array
        {
            return $this->postRequest;
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
                'report_errors' => $this->reportErrors,
                'xss_level' => $this->xssLevel->value,
                'pre_request' => $this->preRequest,
                'post_request' => $this->postRequest
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): SerializableInterface
        {
            return new self($array);
        }
    }