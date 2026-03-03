<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Enums\UserAgent\Bot;
    use DynamicalWeb\Enums\UserAgent\Browser;
    use DynamicalWeb\Enums\UserAgent\DeviceBrand;
    use DynamicalWeb\Enums\UserAgent\DeviceType;
    use DynamicalWeb\Enums\UserAgent\OperatingSystem;
    use DynamicalWeb\Enums\UserAgent\RenderingEngine;
    use DynamicalWeb\Interfaces\SerializableInterface;

    class UserAgent implements SerializableInterface
    {
        private string $rawUserAgent;
        private ?Browser $browserName;
        private ?string $browserVersion;
        private ?string $platform;
        private ?string $platformVersion;
        private ?OperatingSystem $osName;
        private ?string $osVersion;
        private DeviceType $deviceType;
        private ?DeviceBrand $deviceBrand;
        private ?string $deviceModel;
        private bool $isMobile;
        private bool $isTablet;
        private bool $isDesktop;
        private bool $isBot;
        private ?Bot $botName;
        private ?RenderingEngine $engine;
        private ?string $engineVersion;

        /**
         * UserAgent Constructor
         *
         * @param string $userAgent
         */
        public function __construct(string $userAgent)
        {
            $this->rawUserAgent = $userAgent;
            $ua = $this->rawUserAgent;

            // Initialize defaults
            $this->browserName = null;
            $this->browserVersion = null;
            $this->platform = null;
            $this->platformVersion = null;
            $this->osName = null;
            $this->osVersion = null;
            $this->deviceType = DeviceType::UNKNOWN;
            $this->deviceBrand = null;
            $this->deviceModel = null;
            $this->isMobile = false;
            $this->isTablet = false;
            $this->isDesktop = false;
            $this->isBot = false;
            $this->botName = null;
            $this->engine = null;
            $this->engineVersion = null;

            // Detect bots first
            if ($this->detectBot($ua))
            {
                return;
            }

            // Detect device type and OS
            $this->detectDeviceAndOS($ua);

            // If device type is still unknown after OS detection, default to desktop
            if ($this->deviceType === DeviceType::UNKNOWN)
            {
                $this->deviceType = DeviceType::DESKTOP;
                $this->isDesktop = true;
            }

            // Detect browser engine
            $this->detectEngine($ua);

            // Detect browser
            $this->detectBrowser($ua);
        }

        /**
         * Detects if the user-agent is asssociated with a known crawler, bot, or spider and sets the appropriate properties
         *
         * @param string $ua
         * @return bool
         */
        private function detectBot(string $ua): bool
        {
            $bot = Bot::fromUserAgent($ua);
            
            if ($bot !== null)
            {
                $this->isBot = true;
                $this->botName = $bot;
                $this->deviceType = DeviceType::BOT;
                $this->isDesktop = false;
                return true;
            }

            return false;
        }

        /**
         * Detects the operating system and device type based on the user-agent string, setting the appropriate properties
         *
         * @param string $ua
         */
        private function detectDeviceAndOS(string $ua): void
        {
            $this->osName = OperatingSystem::fromUserAgent($ua, $this->osVersion, $this->platform);
            $this->platformVersion = $this->osVersion;

            // Determine device type based on OS
            if ($this->osName === OperatingSystem::IOS)
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->deviceBrand = DeviceBrand::APPLE;
                $this->deviceModel = 'iPhone';
                $this->isMobile = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::IPADOS)
            {
                $this->deviceType = DeviceType::TABLET;
                $this->deviceBrand = DeviceBrand::APPLE;
                $this->deviceModel = 'iPad';
                $this->isTablet = true;
                $this->isDesktop = false;
            }
            elseif (preg_match('/\(iPod;/i', $ua))
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->deviceBrand = DeviceBrand::APPLE;
                $this->deviceModel = 'iPod';
                $this->isMobile = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::WATCHOS)
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->deviceBrand = DeviceBrand::APPLE;
                $this->deviceModel = 'Apple Watch';
                $this->isMobile = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::TVOS)
            {
                $this->deviceType = DeviceType::TABLET;
                $this->deviceBrand = DeviceBrand::APPLE;
                $this->deviceModel = 'Apple TV';
                $this->isTablet = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::ANDROID)
            {
                $this->isMobile = true;
                $this->isDesktop = false;

                // Detect tablet vs mobile
                if (preg_match('/Mobile/i', $ua))
                {
                    $this->deviceType = DeviceType::MOBILE;
                }
                else
                {
                    $this->deviceType = DeviceType::TABLET;
                    $this->isTablet = true;
                    $this->isMobile = false;
                }

                // Detect device brand/model
                $brand = DeviceBrand::fromUserAgent($ua, $this->deviceModel);
                if ($brand !== null)
                {
                    $this->deviceBrand = $brand;
                }
            }
            elseif ($this->osName === OperatingSystem::WINDOWS_PHONE || $this->osName === OperatingSystem::WINDOWS_MOBILE)
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->isMobile = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::BLACKBERRY_OS)
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->deviceBrand = DeviceBrand::BLACKBERRY;
                $this->isMobile = true;
                $this->isDesktop = false;
            }
            elseif ($this->osName === OperatingSystem::WINDOWS || 
                    $this->osName === OperatingSystem::MACOS || 
                    $this->osName === OperatingSystem::LINUX ||
                    $this->osName === OperatingSystem::UBUNTU ||
                    $this->osName === OperatingSystem::FEDORA ||
                    $this->osName === OperatingSystem::DEBIAN ||
                    $this->osName === OperatingSystem::CHROME_OS ||
                    $this->osName === OperatingSystem::REDHAT ||
                    $this->osName === OperatingSystem::CENTOS ||
                    $this->osName === OperatingSystem::ARCH ||
                    $this->osName === OperatingSystem::MINT ||
                    $this->osName === OperatingSystem::SUSE ||
                    $this->osName === OperatingSystem::FREEBSD ||
                    $this->osName === OperatingSystem::OPENBSD ||
                    $this->osName === OperatingSystem::NETBSD ||
                    $this->osName === OperatingSystem::SOLARIS ||
                    $this->osName === OperatingSystem::AIX)
            {
                $this->deviceType = DeviceType::DESKTOP;
                $this->isDesktop = true;
            }
            // Mobile-specific OS
            elseif ($this->osName === OperatingSystem::KAIOS ||
                    $this->osName === OperatingSystem::FIREFOX_OS ||
                    $this->osName === OperatingSystem::SYMBIAN ||
                    $this->osName === OperatingSystem::WEBOS ||
                    $this->osName === OperatingSystem::TIZEN ||
                    $this->osName === OperatingSystem::SAILFISH ||
                    $this->osName === OperatingSystem::HARMONY_OS)
            {
                $this->deviceType = DeviceType::MOBILE;
                $this->isMobile = true;
                $this->isDesktop = false;
            }
        }

        /**
         * Detects the browser rendering engine based on the user-agent string, setting the appropriate properties
         *
         * @param string $ua
         */
        private function detectEngine(string $ua): void
        {
            $this->engine = RenderingEngine::fromUserAgent($ua, $this->engineVersion);
        }

        /**
         * Detects the browser name and version based on the user-agent string, setting the appropriate properties
         *
         * @param string $ua
         */
        private function detectBrowser(string $ua): void
        {
            $this->browserName = Browser::fromUserAgent($ua, $this->browserVersion);
        }

        /**
         * Returns the original raw user-agent string.
         *
         * @return string
         */
        public function getRawUserAgent(): string
        {
            return $this->rawUserAgent;
        }

        /**
         * Returns the detected browser name as a Browser enum value, or null if no browser was detected.
         *
         * @return Browser|null The detected browser name, or null if no browser was detected.
         */
        public function getBrowserName(): ?Browser
        {
            return $this->browserName;
        }

        /**
         * Returns the detected browser version as a string, or null if no version was detected.
         *
         * @return string|null The detected browser version, or null if no version was detected.
         */
        public function getBrowserVersion(): ?string
        {
            return $this->browserVersion;
        }

        /**
         * Returns the full browser name, combining the browser name and version if both are available.
         *
         * @return string|null The full browser name, or null if no browser was detected.
         */
        public function getFullBrowserName(): ?string
        {
            if ($this->browserName === null)
            {
                return null;
            }
            return $this->browserVersion ? $this->browserName->value . ' ' . $this->browserVersion : $this->browserName->value;
        }

        /**
         * Returns the detected platform name as a string, or null if no platform was detected.
         *
         * @return string|null The detected platform name, or null if no platform was detected.
         */
        public function getPlatform(): ?string
        {
            return $this->platform;
        }

        /**
         * Returns the detected platform version as a string, or null if no version was detected.
         *
         * @return string|null The detected platform version, or null if no version was detected.
         */
        public function getPlatformVersion(): ?string
        {
            return $this->platformVersion;
        }

        /**
         * Returns the detected operating system name as an OperatingSystem enum value, or null if no OS was detected.
         *
         * @return OperatingSystem|null The detected operating system name, or null if no OS was detected.
         */
        public function getOsName(): ?OperatingSystem
        {
            return $this->osName;
        }

        /**
         * Returns the detected operating system version as a string, or null if no version was detected.
         *
         * @return string|null The detected operating system version, or null if no version was detected.
         */
        public function getOsVersion(): ?string
        {
            return $this->osVersion;
        }

        /**
         * Returns the full operating system name, combining the OS name and version if both are available.
         *
         * @return string|null The full operating system name, or null if no OS was detected.
         */
        public function getFullOsName(): ?string
        {
            if ($this->osName === null)
            {
                return null;
            }
            return $this->osVersion ? $this->osName->value . ' ' . $this->osVersion : $this->osName->value;
        }

        /**
         * Returns the detected device type as a DeviceType enum value. This will always return a value, defaulting to
         * DeviceType::UNKNOWN if no specific type could be determined.
         *
         * @return DeviceType The detected device type, or DeviceType::UNKNOWN if no specific type could be determined.
         */
        public function getDeviceType(): DeviceType
        {
            return $this->deviceType;
        }

        /**
         * Returns the detected device brand as a DeviceBrand enum value, or null if no brand was detected.
         *
         * @return DeviceBrand|null The detected device brand, or null if no brand was detected.
         */
        public function getDeviceBrand(): ?DeviceBrand
        {
            return $this->deviceBrand;
        }

        /**
         * Returns the detected device model as a string, or null if no model was detected.
         *
         * @return string|null The detected device model, or null if no model was detected.
         */
        public function getDeviceModel(): ?string
        {
            return $this->deviceModel;
        }

        /**
         * Returns the full device name, combining the device brand and model if both are available. If only one of brand or model is available, returns that. If neither is available, returns null.
         *
         * @return string|null The full device name, or null if no brand or model was detected.
         */
        public function getFullDeviceName(): ?string
        {
            if ($this->deviceBrand === null && $this->deviceModel === null)
            {
                return null;
            }
            
            if ($this->deviceBrand && $this->deviceModel)
            {
                return $this->deviceBrand->value . ' ' . $this->deviceModel;
            }
            
            return $this->deviceBrand?->value ?? $this->deviceModel;
        }

        /**
         * Returns true if the user-agent is detected as a mobile device, false otherwise.
         *
         * @return bool True if the user-agent is detected as a mobile device, false otherwise.
         */
        public function isMobile(): bool
        {
            return $this->isMobile;
        }

        /**
         * Returns true if the user-agent is detected as a tablet device, false otherwise.
         *
         * @return bool True if the user-agent is detected as a tablet device, false otherwise.
         */
        public function isTablet(): bool
        {
            return $this->isTablet;
        }

        /**
         * Returns true if the user-agent is detected as a desktop device, false otherwise.
         *
         * @return bool True if the user-agent is detected as a desktop device, false otherwise.
         */
        public function isDesktop(): bool
        {
            return $this->isDesktop;
        }

        /**
         * Returns true if the user-agent is detected as a bot/crawler/spider, false otherwise.
         *
         * @return bool True if the user-agent is detected as a bot/crawler/spider, false otherwise.
         */
        public function isBot(): bool
        {
            return $this->isBot;
        }

        /**
         * Returns the detected bot name as a Bot enum value, or null if no bot was detected.
         *
         * @return Bot|null The detected bot name, or null if no bot was detected.
         */
        public function getBotName(): ?Bot
        {
            return $this->botName;
        }

        /**
         * Returns the detected browser rendering engine as a RenderingEngine enum value, or null if no engine was detected.
         *
         * @return RenderingEngine|null The detected browser rendering engine, or null if no engine was detected.
         */
        public function getEngine(): ?RenderingEngine
        {
            return $this->engine;
        }

        /**
         * Returns the detected browser rendering engine version as a string, or null if no version was detected.
         *
         * @return string|null The detected browser rendering engine version, or null if no version was detected.
         */
        public function getEngineVersion(): ?string
        {
            return $this->engineVersion;
        }

        /**
         * Returns the full rendering engine name, combining the engine name and version if both are available.
         *
         * @return string|null The full rendering engine name, or null if no engine was detected.
         */
        public function getFullEngineName(): ?string
        {
            if ($this->engine === null)
            {
                return null;
            }
            return $this->engineVersion ? $this->engine->value . ' ' . $this->engineVersion : $this->engine->value;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'raw' => $this->rawUserAgent,
                'browser' => [
                    'name' => $this->browserName?->value,
                    'version' => $this->browserVersion,
                    'full' => $this->getFullBrowserName(),
                ],
                'os' => [
                    'name' => $this->osName?->value,
                    'version' => $this->osVersion,
                    'full' => $this->getFullOsName(),
                ],
                'platform' => [
                    'name' => $this->platform,
                    'version' => $this->platformVersion,
                ],
                'device' => [
                    'type' => $this->deviceType->value,
                    'brand' => $this->deviceBrand?->value,
                    'model' => $this->deviceModel,
                    'full' => $this->getFullDeviceName(),
                ],
                'engine' => [
                    'name' => $this->engine?->value,
                    'version' => $this->engineVersion,
                    'full' => $this->getFullEngineName(),
                ],
                'flags' => [
                    'is_mobile' => $this->isMobile,
                    'is_tablet' => $this->isTablet,
                    'is_desktop' => $this->isDesktop,
                    'is_bot' => $this->isBot,
                ],
                'bot' => [
                    'name' => $this->botName?->value,
                ],
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): UserAgent
        {
            $userAgent = new self($array['raw'] ?? '');
            $userAgent->browserName = isset($array['browser']['name']) ? Browser::tryFrom($array['browser']['name']) : null;
            $userAgent->browserVersion = $array['browser']['version'] ?? null;
            $userAgent->platform = $array['platform']['name'] ?? null;
            $userAgent->platformVersion = $array['platform']['version'] ?? null;
            $userAgent->osName = isset($array['os']['name']) ? OperatingSystem::tryFrom($array['os']['name']) : null;
            $userAgent->osVersion = $array['os']['version'] ?? null;
            $userAgent->deviceType = isset($array['device']['type']) ? DeviceType::tryFrom($array['device']['type']) : DeviceType::UNKNOWN;
            $userAgent->deviceBrand = isset($array['device']['brand']) ? DeviceBrand::tryFrom($array['device']['brand']) : null;
            $userAgent->deviceModel = $array['device']['model'] ?? null;
            $userAgent->isMobile = $array['flags']['is_mobile'] ?? false;
            $userAgent->isTablet = $array['flags']['is_tablet'] ?? false;
            $userAgent->isDesktop = $array['flags']['is_desktop'] ?? false;
            $userAgent->isBot = $array['flags']['is_bot'] ?? false;
            $userAgent->botName = isset($array['bot']['name']) ? Bot::tryFrom($array['bot']['name']) : null;
            $userAgent->engine = isset($array['engine']['name']) ? RenderingEngine::tryFrom($array['engine']['name']) : null;
            $userAgent->engineVersion = $array['engine']['version'] ?? null;

            return $userAgent;
        }

        /**
         * Returns a string representation of the UserAgent object, which is the original raw user-agent string.
         *
         * @return string The original raw user-agent string.
         */
        public function __toString(): string
        {
            return $this->rawUserAgent;
        }
    }
