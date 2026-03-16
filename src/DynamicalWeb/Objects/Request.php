<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Classes\Apcu;
    use DynamicalWeb\Classes\Router;
    use DynamicalWeb\DynamicalWeb;
    use DynamicalWeb\Enums\LanguageCode;
    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\RequestMethod;
    use Symfony\Component\Yaml\Exception\ParseException;
    use Symfony\Component\Yaml\Yaml;

    class Request
    {
        private const string ACCEPT_LANGUAGE_REGEX = '/^([a-z]{2,3})(?:-([A-Z]{2}))?(?:;q=([0-9.]+))?$/i';

        private string $id;
        private RequestMethod $method;
        private string $url;
        private string $path;
        private string $host;
        private string $httpVersion;
        private bool $isSecure;
        private array $headers;
        private array $queryParameters;
        private array $bodyParameters;
        private array $formParameters;
        private array $pathParameters;
        private array $files;
        private array $uploadedFiles;
        private array $cookies;
        private ?string $detectedLanguage;
        private ?string $clientIp;
        private ?string $rawBody;
        private array $headersLowerMap;
        private ?UserAgent $userAgent;
        private ?string $rawUserAgentString;

        /**
         * Request Constructor
         *
         * @param WebConfiguration $webConfiguration Optional WebConfiguration for extracting path parameters
         * @param string $package Optional package name for route matching when extracting path parameters
         * @param DynamicalWeb $dynamicalWeb Optional DynamicalWeb instance for language detection based on available locales
         */
        public function __construct(WebConfiguration $webConfiguration, string $package, DynamicalWeb $dynamicalWeb)
        {
            // Request ID
            $this->id = uniqid();
            $this->method = RequestMethod::from($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $this->isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? 80) == 443
                || (strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
            $this->host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
            $this->httpVersion = str_replace('HTTP/', '', ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1'));
            $this->path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
            $this->url = ($this->isSecure ? 'https' : 'http') . '://' . $this->host . ($_SERVER['REQUEST_URI'] ?? '/');
            $this->queryParameters = $_GET ?? [];
            $this->bodyParameters = $_POST ?? [];
            $this->formParameters = $_POST ?? [];

            // Handle raw input for JSON or other content types
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (stripos($contentType, MimeType::JSON->value) !== false)
            {
                $rawInput = file_get_contents('php://input');
                if ($rawInput)
                {
                    $decoded = json_decode($rawInput, true);
                    if (json_last_error() === JSON_ERROR_NONE)
                    {
                        $this->bodyParameters = $decoded ?? [];
                    }
                }
            }
            elseif (stripos($contentType, MimeType::YAML->value) !== false)
            {
                $rawInput = file_get_contents('php://input');
                if ($rawInput)
                {
                    try
                    {
                        $decoded = Yaml::parse($rawInput);
                    }
                    catch(ParseException)
                    {
                        $decoded = null;
                    }

                    if ($decoded !== null)
                    {
                        $this->bodyParameters = is_array($decoded) ? $decoded : [];
                    }
                }
            }

            $this->files = $_FILES ?? [];
            $this->uploadedFiles = [];
            $this->cookies = $_COOKIE ?? [];
            $this->headers = $this->parseHeaders();
            $this->headersLowerMap = array_change_key_case($this->headers);
            $this->pathParameters = [];
            $this->clientIp = $this->detectClientIp();
            $this->rawBody = null;
            $this->rawUserAgentString = $this->getHeader('User-Agent');
            $this->userAgent = null;

            // Parse uploaded files into UploadedFile objects
            $this->parseUploadedFiles();
            $this->pathParameters = Router::extractPathParameters($webConfiguration, $this);

            // Detect language from Accept-Language header
            $this->detectedLanguage = null;
            $this->detectedLanguage = $this->detectLanguage($dynamicalWeb->getAvailableLocaleCodes());
        }

        /**
         * Returns the unique identifier for this request.
         *
         * @return string The unique request ID
         */
        public function getId(): string
        {
            return $this->id;
        }

        /**
         * Returns the HTTP method of the request.
         *
         * @return RequestMethod The HTTP method (GET, POST, etc.)
         */
        public function getMethod(): RequestMethod
        {
            return $this->method;
        }

        /**
         * Returns the full URL of the request.
         *
         * @return string The full request URL
         */
        public function getUrl(): string
        {
            return $this->url;
        }

        /**
         * Returns the path component of the request URL.
         *
         * @return string The request path
         */
        public function getPath(): string
        {
            return $this->path;
        }

        /**
         * Returns the host of the request.
         *
         * @return string The request host
         */
        public function getHost(): string
        {
            return $this->host;
        }

        /**
         * Returns the HTTP version of the request.
         *
         * @return string The HTTP version (e.g., "1.1", "2.0")
         */
        public function getHttpVersion(): string
        {
            return $this->httpVersion;
        }

        /**
         * Indicates whether the request was made over HTTPS.
         *
         * @return bool True if the request is secure (HTTPS), false otherwise
         */
        public function isSecure(): bool
        {
            return $this->isSecure;
        }

        /**
         * Returns all headers of the request.
         *
         * @return array An associative array of headers (Header-Name => value)
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }
        /**
         * Returns all query string parameters from the URL.
         *
         * @return array<string, mixed> Associative array of query parameters
         */
        public function getQueryParameters(): array
        {
            return $this->queryParameters;
        }

        /**
         * Returns all body parameters from POST or JSON request body.
         *
         * @return array<string, mixed> Associative array of body parameters
         */
        public function getBodyParameters(): array
        {
            return $this->bodyParameters;
        }

        /**
         * Returns all form parameters from POST request.
         *
         * @return array<string, mixed> Associative array of form parameters
         */
        public function getFormParameters(): array
        {
            return $this->formParameters;
        }

        /**
         * Returns all path parameters extracted from the route.
         *
         * @return array<string, string> Associative array of path parameters
         */
        public function getPathParameters(): array
        {
            return $this->pathParameters;
        }

        /**
         * Returns a specific path parameter by name.
         *
         * @param string $name The parameter name
         * @return string|null The parameter value, or null if not found
         */
        public function getPathParameter(string $name): ?string
        {
            return $this->pathParameters[$name] ?? null;
        }

        /**
         * Returns all parameters merged from query, body, and form parameters.
         * Form parameters take precedence over body, which takes precedence over query.
         *
         * @return array<string, mixed> Merged array of all parameters
         */
        public function getParameters(): array
        {
            // Merge query, body, and form parameters (form parameters take precedence over body, which takes precedence over query)
            return array_merge($this->queryParameters, $this->bodyParameters, $this->formParameters);
        }

        /**
         * Returns a specific parameter by name from merged parameters.
         *
         * @param string $name The parameter name
         * @return string|null The parameter value, or null if not found
         */
        public function getParameter(string $name): ?string
        {
            return $this->getParameters()[$name] ?? null;
        }

        /**
         * Returns all raw uploaded files from $_FILES.
         *
         * @return array<string, array> Raw files array
         */
        public function getFiles(): array
        {
            return $this->files;
        }

        /**
         * Returns all request cookies.
         *
         * @return array<string, string> Associative array of cookies
         */
        public function getCookies(): array
        {
            return $this->cookies;
        }

        /**
         * Returns a specific cookie value by name.
         *
         * @param string $name The cookie name
         * @param mixed $default Default value if cookie not found
         * @return mixed The cookie value, or default if not found
         */
        public function getCookie(string $name, $default = null)
        {
            return $this->cookies[$name] ?? $default;
        }

        /**
         * Returns a specific header value by name (case-insensitive).
         *
         * @param string $name The header name
         * @param string|null $default Default value if header not found
         * @return string|null The header value, or default if not found
         */
        public function getHeader(string $name, ?string $default = null): ?string
        {
            $nameLower = strtolower($name);
            return $this->headersLowerMap[$nameLower] ?? $default;
        }
        
        /**
         * Returns the parsed User Agent object (lazily initialized on first access).
         *
         * @return UserAgent|null The parsed User Agent, or null if not available
         */
        public function getUserAgent(): ?UserAgent
        {
            if ($this->userAgent === null && $this->rawUserAgentString !== null)
            {
                $this->userAgent = new UserAgent($this->rawUserAgentString);
            }
            return $this->userAgent;
        }

        /**
         * Returns the raw User-Agent string from the request.
         *
         * @return string|null The raw User-Agent string, or null if not available
         */
        public function getUserAgentString(): ?string
        {
            return $this->rawUserAgentString;
        }
        
        /**
         * Returns the detected client IP address.
         *
         * @return string|null The client IP address, or null if not detected
         */
        public function getClientIp(): ?string
        {
            return $this->clientIp;
        }

        /**
         * Returns the raw request body content.
         * Reads from php://input if not already cached.
         *
         * @return string|null The raw request body, or null if empty
         */
        public function getRawBody(): ?string
        {
            if ($this->rawBody === null)
            {
                $this->rawBody = file_get_contents('php://input');
            }
            return $this->rawBody;
        }

        /**
         * Parses HTTP headers from $_SERVER superglobal.
         *
         * @return array<string, string> Parsed headers array
         */
        private function parseHeaders(): array
        {
            $headers = [];

            // Parse headers from $_SERVER
            foreach ($_SERVER as $key => $value)
            {
                if (str_starts_with($key, 'HTTP_'))
                {
                    // Convert HTTP_HEADER_NAME to Header-Name format
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                    $headers[$headerName] = $value;
                }
                elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5']))
                {
                    // These headers don't have HTTP_ prefix
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$headerName] = $value;
                }
            }

            // Alternative: use getallheaders() if available (Apache)
            if (function_exists('getallheaders'))
            {
                $allHeaders = getallheaders();
                if ($allHeaders !== false)
                {
                    $headers = array_merge($headers, $allHeaders);
                }
            }

            return $headers;
        }

        /**
         * Detects the client IP address from various proxy headers and REMOTE_ADDR.
         * Checks headers in priority order: Cloudflare, X-Forwarded-For, X-Real-IP, Client-IP, Remote-Addr.
         *
         * @return string|null The detected IP address, or null if not found
         */
        private function detectClientIp(): ?string
        {
            $ipHeaders = [
                'HTTP_CF_CONNECTING_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_REAL_IP',
                'HTTP_CLIENT_IP',
                'REMOTE_ADDR'
            ];

            foreach ($ipHeaders as $header)
            {
                if (!empty($_SERVER[$header]))
                {
                    $ip = $_SERVER[$header];
                    if ($header === 'HTTP_X_FORWARDED_FOR')
                    {
                        $ips = array_map('trim', explode(',', $ip));
                        $ip = $ips[0];
                    }
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))
                    {
                        return $ip;
                    }
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP))
                    {
                        return $ip;
                    }
                }
            }

            return null;
        }

        /**
         * Detects the best matching language from the Accept-Language header against available locales.
         * Automatically normalizes language codes to ISO 639-1 format.
         *
         * @param array $availableLocales An array of available ISO 639-1 locale codes
         * @return string|null The best matching locale code, or null if no match found
         */
        private function detectLanguage(array $availableLocales): ?string
        {
            if (empty($availableLocales))
            {
                return null;
            }

            $acceptLanguage = $this->getHeader('Accept-Language');
            if ($acceptLanguage === null)
            {
                return null;
            }

            // APCu cache: Accept-Language + locales fingerprint → detected code
            $cacheKey = 'dw_lang_' . md5($acceptLanguage . implode(',', $availableLocales));
            $cached   = Apcu::fetch($cacheKey, $hit);
            if ($hit)
            {
                return $cached !== '' ? $cached : null;
            }

            $result = $this->resolveLanguage($acceptLanguage, $availableLocales);
            Apcu::store($cacheKey, $result ?? '', 3600);
            return $result;
        }

        /**
         * Core Accept-Language resolution logic (called on APCu miss).
         *
         * @param string $acceptLanguage The raw Accept-Language header value
         * @param array $availableLocales An array of available ISO 639-1 locale codes
         *
         * @return string|null The best matching locale code, or null if no match found
         */
        private function resolveLanguage(string $acceptLanguage, array $availableLocales): ?string
        {
            // Parse Accept-Language header: "en-US,en;q=0.9,fr;q=0.8"
            $languages = [];
            $parts = explode(',', $acceptLanguage);
            
            foreach ($parts as $part)
            {
                $part = trim($part);
                if (preg_match(self::ACCEPT_LANGUAGE_REGEX, $part, $matches))
                {
                    $langCode = strtolower($matches[1]);
                    $region = isset($matches[2]) ? strtoupper($matches[2]) : null;
                    $quality = isset($matches[3]) ? (float)$matches[3] : 1.0;
                    
                    $fullCode = $region ? $langCode . '-' . $region : $langCode;
                    $languages[$fullCode] = ['code' => $langCode, 'quality' => $quality];
                }
            }

            // Sort by quality (highest first)
            uasort($languages, function($a, $b) {
                return $b['quality'] <=> $a['quality'];
            });

            // Match against available locales
            foreach ($languages as $fullCode => $info)
            {
                $langCode = $info['code'];
                $normalizedCode = LanguageCode::normalize($langCode);
                
                // Try exact match first (e.g., "en-US")
                if (in_array($fullCode, $availableLocales, true))
                {
                    return $fullCode;
                }
                
                // Try normalized code
                if (in_array($normalizedCode, $availableLocales, true))
                {
                    return $normalizedCode;
                }
                
                // Try base language code (e.g., "en" from "en-US")
                if (strlen($normalizedCode) === 2 && in_array($normalizedCode, $availableLocales, true))
                {
                    return $normalizedCode;
                }
            }

            return null;
        }

        /**
         * Returns the detected language code based on the Accept-Language header.
         *
         * @return string|null The detected ISO 639-1 language code, or null if not detected
         */
        public function getDetectedLanguage(): ?string
        {
            return $this->detectedLanguage;
        }

        /**
         * Parse uploaded files from $_FILES into UploadedFile objects.
         * Handles both single file and multiple file uploads.
         */
        private function parseUploadedFiles(): void
        {
            foreach ($this->files as $key => $file)
            {
                // Handle multiple file uploads (array of files)
                if (is_array($file['name']))
                {
                    $this->uploadedFiles[$key] = [];
                    $count = count($file['name']);
                    
                    for ($i = 0; $i < $count; $i++)
                    {
                        $this->uploadedFiles[$key][] = new UploadedFile(
                            $file['name'][$i] ?? '',
                            $file['tmp_name'][$i] ?? '',
                            $file['size'][$i] ?? 0,
                            $file['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                            $file['type'][$i] ?? null
                        );
                    }
                }
                // Handle single file upload
                else
                {
                    $this->uploadedFiles[$key] = new UploadedFile(
                        $file['name'] ?? '',
                        $file['tmp_name'] ?? '',
                        $file['size'] ?? 0,
                        $file['error'] ?? UPLOAD_ERR_NO_FILE,
                        $file['type'] ?? null
                    );
                }
            }
        }

        /**
         * Check if request has any file uploads.
         *
         * @return bool True if files were uploaded, false otherwise
         */
        public function hasFiles(): bool
        {
            return !empty($this->uploadedFiles);
        }

        /**
         * Check if a specific file upload exists.
         * 
         * @param string $key The form field name
         * @return bool True if the file field exists, false otherwise
         */
        public function hasFile(string $key): bool
        {
            return isset($this->uploadedFiles[$key]);
        }

        /**
         * Get an uploaded file by key.
         * 
         * @param string $key The form field name
         * @return UploadedFile|array<UploadedFile>|null Single file, array of files, or null if not found
         */
        public function getFile(string $key): UploadedFile|array|null
        {
            return $this->uploadedFiles[$key] ?? null;
        }

        /**
         * Get count of uploaded files.
         * Counts individual files even if uploaded as arrays.
         *
         * @return int Total number of uploaded files
         */
        public function getFileCount(): int
        {
            $count = 0;
            
            foreach ($this->uploadedFiles as $file)
            {
                if (is_array($file))
                {
                    $count += count($file);
                }
                else
                {
                    $count++;
                }
            }
            
            return $count;
        }

        /**
         * Get all valid uploaded files (no errors).
         * Filters out files that failed to upload or have errors.
         * 
         * @return array<string, UploadedFile|array<UploadedFile>> Array of valid UploadedFile objects
         */
        public function getValidFiles(): array
        {
            $valid = [];
            
            foreach ($this->uploadedFiles as $key => $file)
            {
                if (is_array($file))
                {
                    $validInArray = array_filter($file, fn($f) => $f->isValid());
                    if (!empty($validInArray))
                    {
                        $valid[$key] = array_values($validInArray);
                    }
                }
                elseif ($file->isValid())
                {
                    $valid[$key] = $file;
                }
            }
            
            return $valid;
        }

        /**
         * Get total size of all uploaded files in bytes.
         * Includes all files even if they have errors.
         *
         * @return int Total size in bytes
         */
        public function getTotalFileSize(): int
        {
            $total = 0;
            
            foreach ($this->uploadedFiles as $file)
            {
                if (is_array($file))
                {
                    foreach ($file as $f)
                    {
                        $total += $f->getSize();
                    }
                }
                else
                {
                    $total += $file->getSize();
                }
            }
            
            return $total;
        }
    }