<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Enums\ResponseType;
    use DynamicalWeb\WebSession;
    use InvalidArgumentException;
    use Symfony\Component\Yaml\Yaml;

    class Response
    {
        private ResponseCode $statusCode;
        private string $httpVersion;
        private array $headers;
        private string $body;
        private string $contentType;
        private string $charset;
        /**
         * @var array<string, Cookie>
         */
        private array $cookies;
        private ResponseType $responseType;
        private ?string $filePath;
        /**
         * @var callable|null
         */
        private $streamCallback;

        /**
         * Response constructor.
         */
        public function __construct()
        {
            // Default response configuration
            $this->statusCode = ResponseCode::OK;
            $this->httpVersion = '1.1';
            $this->headers = [];
            $this->body = '';
            $this->contentType = 'text/html';
            $this->charset = 'UTF-8';
            $this->cookies = [];
            $this->responseType = ResponseType::BASIC;
            $this->filePath = null;
            $this->streamCallback = null;
        }

        /**
         * Returns the HTTP status code of the response.
         *
         * @return ResponseCode The HTTP status code of the response.
         */
        public function getStatusCode(): ResponseCode
        {
            return $this->statusCode;
        }

        /**
         * Sets the HTTP status code of the response.
         *
         * @param ResponseCode|int $statusCode The HTTP status code to set for the response.
         * @return self Returns the Response object for method chaining.
         */
        public function setStatusCode(ResponseCode|int $statusCode): self
        {
            if(is_int($statusCode))
            {
                $statusCode = ResponseCode::tryFrom($statusCode);
                if($statusCode === null)
                {
                    throw new InvalidArgumentException("Invalid status code: {$statusCode}");
                }
            }

            $this->statusCode = $statusCode;
            return $this;
        }

        /**
         * Returns the HTTP version of the response.
         *
         * @return string The HTTP version of the response.
         */
        public function getHttpVersion(): string
        {
            return $this->httpVersion;
        }

        /**
         * Sets the HTTP version of the response.
         *
         * @param string $httpVersion The HTTP version to set for the response (e.g., '1.0', '1.1', '2').
         * @return self Returns the Response object for method chaining.
         */
        public function setHttpVersion(string $httpVersion): self
        {
            $this->httpVersion = $httpVersion;
            return $this;
        }

        /**
         * Returns the headers of the response as an associative array where the keys are header names and the values are either strings or arrays of strings (for headers that can have multiple values).
         *
         * @return array<string, string|array<string>> An associative array of headers for the response, where the keys are header names and the values are either strings or arrays of strings.
         */
        public function getHeaders(): array
        {
            return $this->headers;
        }

        /**
         * Sets the headers for the response. The headers should be provided as an associative array where the keys are
         * header names and the values are either strings or arrays of strings (for headers that can have multiple values).
         *
         * @param array<string, string|array<string>> $headers An associative array of headers to set for the response,
         *        where the keys are header names and the values are either strings or arrays of strings.
         * @return self Returns the Response object for method chaining.
         */
        public function setHeaders(array $headers): self
        {
            $this->headers = $headers;
            return $this;
        }

        /**
         * Sets a header for the response.
         *
         * @param string $name The name of the header to set.
         * @param string $value The value of the header to set.
         * @param bool $replace If true (default), replaces existing header. If false, appends to existing values
         *        (useful for Set-Cookie, Vary, Link, etc.).
         * @return self Returns the Response object for method chaining.
         */
        public function setHeader(string $name, string $value, bool $replace=true): self
        {
            if ($replace)
            {
                $this->headers[$name] = $value;
            }
            else
            {
                if (isset($this->headers[$name]))
                {
                    if (!is_array($this->headers[$name]))
                    {
                        $this->headers[$name] = [$this->headers[$name]];
                    }

                    $this->headers[$name][] = $value;
                }
                else
                {
                    $this->headers[$name] = $value;
                }
            }
            return $this;
        }

        /**
         * Removes a header from the response.
         *
         * @param string $name The name of the header to remove.
         * @return self Returns the Response object for method chaining.
         */
        public function removeHeader(string $name): self
        {
            unset($this->headers[$name]);
            return $this;
        }

        /**
         * Returns the body content of the response.
         *
         * @return string The body content of the response.
         */
        public function getBody(): string
        {
            return $this->body;
        }

        /**
         * Sets the body content of the response.
         *
         * @param string $body The body content to set for the response.
         * @return self Returns the Response object for method chaining.
         */
        public function setBody(string $body): self
        {
            $this->body = $body;
            return $this;
        }

        /**
         * Returns the Content-Type of the response.
         *
         * @return string The Content-Type of the response.
         */
        public function getContentType(): string
        {
            return $this->contentType;
        }

        /**
         * Sets the Content-Type of the response.
         *
         * @param string|MimeType $contentType The Content-Type to set for the response, either as a string or as a
         *        MimeType enum value.
         * @return self Returns the Response object for method chaining.
         */
        public function setContentType(string|MimeType $contentType): self
        {
            if($contentType instanceof MimeType)
            {
                $contentType = $contentType->value;
            }

            $this->contentType = $contentType;
            return $this;
        }

        /**
         * Returns the character set of the response.
         *
         * @return string The character set of the response (e.g., 'UTF-8').
         */
        public function getCharset(): string
        {
            return $this->charset;
        }

        /**
         * Sets the character set of the response.
         *
         * @param string $charset The character set to set for the response (e.g., 'UTF-8').
         * @return self Returns the Response object for method chaining.
         */
        public function setCharset(string $charset): self
        {
            $this->charset = $charset;
            return $this;
        }

        /**
         * Gets the cookies set for the response as an associative array where the keys are cookie names and the values
         * are Cookie objects.
         *
         * @return array<string, Cookie> An associative array of cookies for the response, where the keys are cookie
         *         names and the values are Cookie objects.
         */
        public function getCookies(): array
        {
            return $this->cookies;
        }

        /**
         * Gets a specific cookie by name from the response.
         *
         * @param string $name The name of the cookie to retrieve.
         * @return Cookie|null The Cookie object if found, or null if no cookie with the given name exists in the response.
         */
        public function getCookie(string $name): ?Cookie
        {
            return $this->cookies[$name] ?? null;
        }

        /**
         * Sets the cookies for the response. The cookies should be provided as an associative array where the keys are
         * cookie names and the values are Cookie objects.
         *
         * @param array<string, Cookie> $cookies An associative array of cookies to set for the response, where the keys
         *        are cookie names and the values are Cookie objects.
         * @return self Returns the Response object for method chaining.
         */
        public function setCookies(array $cookies): self
        {
            $this->cookies = $cookies;
            return $this;
        }

        /**
         * Sets a cookie for the response.
         *
         * @param string $name The name of the cookie to set.
         * @param string $value The value of the cookie to set.
         * @param int $expires The expiration time of the cookie as a Unix timestamp. Default is 0 (session cookie).
         * @param string $path The path on the server where the cookie will be available. Default is '/'.
         * @param string $domain The domain that the cookie is available to. Default is '' (current domain).
         * @param bool $secure Whether the cookie should only be transmitted over secure HTTPS connections. Default is false.
         * @param bool $httpOnly Whether the cookie should be accessible only through the HTTP protocol and not accessible
         *                       via JavaScript. Default is false.
         * @return self Returns the Response object for method chaining.
         */
        public function setCookie(string $name, string $value, int $expires = 0, string $path = '/', string $domain = '', bool $secure = false, bool $httpOnly = false): self
        {
            $this->cookies[$name] = new Cookie($name, $value, $expires, $path, $domain, $secure, $httpOnly);
            return $this;
        }

        /**
         * Adds a Cookie object to the response.
         *
         * @param Cookie $cookie The Cookie object to add to the response.
         * @return self Returns the Response object for method chaining.
         */
        public function addCookie(Cookie $cookie): self
        {
            $this->cookies[$cookie->getName()] = $cookie;
            return $this;
        }

        /**
         * Removes a cookie from the response by name.
         *
         * @param string $name The name of the cookie to remove from the response.
         * @return self Returns the Response object for method chaining.
         */
        public function removeCookie(string $name): self
        {
            unset($this->cookies[$name]);
            return $this;
        }

        /**
         * Returns the type of the response.
         *
         * @return ResponseType The type of the response (e.g., BASIC, JSON, FILE_DOWNLOAD, REDIRECT, STREAM).
         */
        public function getResponseType(): ResponseType
        {
            return $this->responseType;
        }

        /**
         * Sets the type of the response.
         *
         * @param ResponseType $responseType The type to set for the response (e.g., BASIC, JSON, FILE_DOWNLOAD, REDIRECT, STREAM).
         * @return self Returns the Response object for method chaining.
         */
        public function setResponseType(ResponseType $responseType): self
        {
            $this->responseType = $responseType;
            return $this;
        }

        /**
         * Returns the file path associated with the response, if the response type is FILE_DOWNLOAD.
         *
         * @return string|null The file path for the response if the response type is FILE_DOWNLOAD, or null if no
         *                     file path is set or if the response type is not FILE_DOWNLOAD.
         */
        public function getFilePath(): ?string
        {
            return $this->filePath;
        }

        /**
         * Returns the stream callback associated with the response, if the response type is STREAM.
         *
         * @return callable|null The stream callback for the response if the response type is STREAM, or null if no
         *                       stream callback is set or if the response type is not STREAM.
         */
        public function getStreamCallback()
        {
            return $this->streamCallback;
        }

        /**
         * Sets the response body to a JSON-encoded string of the provided data and updates the content type to 'application/json'.
         *
         * @param mixed $data The data to be JSON-encoded and set as the response body.
         * @param int $flags Optional JSON encoding flags (e.g., JSON_PRETTY_PRINT). Default is 0 (no flags).
         * @param int $depth Optional maximum depth for JSON encoding. Default is 512.
         * @return self Returns the Response object for method chaining.
         */
        public function setJson(mixed $data, int $flags=0, int $depth=512): self
        {
            $this->responseType = ResponseType::JSON;
            $this->contentType = 'application/json';
            $this->body = json_encode($data, $flags, $depth);
            return $this;
        }

        /**
         * Sets the response body to a YAML-encoded string of the provided data and updates the content type to 'application/yaml'.
         *
         * @param mixed $data The data to be YAML-encoded and set as the response body.
         * @param int $inline The level at which to switch to inline YAML. Default is 2.
         * @param int $indent The number of spaces to use for indentation. Default is 4.
         * @param int $flags Optional YAML encoding flags. Default is 0.
         * @return self Returns the Response object for method chaining.
         */
        public function setYaml(mixed $data, int $inline=2, int $indent=4, int $flags=0): self
        {
            $this->responseType = ResponseType::YAML;
            $this->contentType = 'application/yaml';
            $this->body = Yaml::dump($data, $inline, $indent, $flags);
            return $this;
        }

        /**
         * Sets the response to trigger a file download in the client's browser for the specified file path.
         * The method also sets appropriate headers for content disposition, content length, and content type based on
         * the file extension.
         *
         * @param string $filePath The path to the file to be downloaded. Must be a valid file path on the server.
         * @param string|null $filename Optional filename to suggest for the downloaded file. If null, the original
         *        filename from the file path will be used.
         * @return self Returns the Response object for method chaining.
         * @throws InvalidArgumentException If the specified file path does not exist.
         */
        public function setFileDownload(string $filePath, ?string $filename=null): self
        {
            if (!file_exists($filePath))
            {
                throw new InvalidArgumentException("File not found: {$filePath}");
            }

            $this->responseType = ResponseType::FILE_DOWNLOAD;
            $this->filePath = $filePath;
            
            if ($filename === null)
            {
                $filename = basename($filePath);
            }

            $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
            $this->setHeader('Content-Length', (string)filesize($filePath));
            
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);
            $mimeType = MimeType::fromExtension($extension);
            $this->contentType = $mimeType->value;
            
            return $this;
        }

        /**
         * Sets the response to redirect the client to the specified URL with an optional HTTP status code (default is 302 Found).
         *
         * @param string $url The URL to redirect the client to. Must be a valid URL.
         * @param ResponseCode|null $statusCode Optional HTTP status code to use for the redirect. If null, defaults to
         *        ResponseCode::FOUND (302).
         * @return self Returns the Response object for method chaining.
         */
        public function setRedirect(string $url, ResponseCode $statusCode=null): self
        {
            $this->responseType = ResponseType::REDIRECT;
            
            if ($statusCode === null)
            {
                $statusCode = ResponseCode::FOUND;
            }
            
            $this->statusCode = $statusCode;
            $this->setHeader('Location', $url);
            
            return $this;
        }

        /**
         * Sets the response to be a stream response, where the content is generated dynamically by a callback function.
         * The method also sets appropriate headers to prevent caching and buffering of the stream.
         *
         * @param callable $callback A callback function that generates the stream content. The callback should return
         *        a string of data to be sent to the client, or false/null to indicate the end of the stream.
         * @return self Returns the Response object for method chaining.
         */
        public function setStream(callable $callback): self
        {
            $this->responseType = ResponseType::STREAM;
            $this->streamCallback = $callback;
            $this->setHeader('Cache-Control', 'no-cache');
            $this->setHeader('X-Accel-Buffering', 'no');
            
            return $this;
        }

        /**
         * Sends the response to the client by outputting the appropriate headers, cookies, and body content based on
         * the response type and configuration.
         */
        public function send(): void
        {
            // Send status code
            http_response_code($this->statusCode->value);

            // Send content type header
            header('Content-Type: ' . $this->contentType . '; charset=' . $this->charset);

            // Send custom headers
            foreach ($this->headers as $name => $value)
            {
                if (is_array($value))
                {
                    foreach ($value as $val)
                    {
                        header($name . ': ' . $val, false);
                    }
                }
                else
                {
                    header($name . ': ' . $value);
                }
            }

            // Set cookies
            foreach ($this->cookies as $cookie)
            {
                setcookie(
                    name: $cookie->getName(),
                    value: $cookie->getValue(),
                    expires_or_options: $cookie->getExpires(),
                    path: $cookie->getPath(),
                    domain: $cookie->getDomain(),
                    secure: $cookie->isSecure(),
                    httponly: $cookie->isHttpOnly()
                );
            }

            // Handle response based on type
            switch ($this->responseType)
            {
                case ResponseType::BASIC:
                case ResponseType::JSON:
                case ResponseType::YAML:
                    if (!empty($this->body))
                    {
                        print($this->body);
                    }
                    break;

                case ResponseType::FILE_DOWNLOAD:
                    if ($this->filePath !== null && file_exists($this->filePath))
                    {
                        $this->streamFile($this->filePath);
                    }
                    break;

                case ResponseType::REDIRECT:
                    // Headers already set, no body needed
                    break;

                case ResponseType::STREAM:
                    if ($this->streamCallback !== null)
                    {
                        $this->handleStream($this->streamCallback);
                    }
                    break;
            }
        }

        /**
         * Streams a file to the client by reading it in chunks and outputting the data until the end of the file is reached.
         *
         * @param string $filePath The path to the file to be streamed. Must be a valid file path on the server.
         */
        private function streamFile(string $filePath): void
        {
            $handle = fopen($filePath, 'rb');
            if ($handle === false)
            {
                return;
            }

            while (!feof($handle))
            {
                print(fread($handle, 8192));
                flush();
            }

            fclose($handle);
        }

        /**
         * Handles a stream response by repeatedly calling the provided callback function to generate content and
         * outputting it to the client until the callback indicates that the stream has ended.
         *
         * @param callable $callback A callback function that generates the stream content. The callback should return a
         *        string of data to be sent to the client, or false/null to indicate the end of the stream.
         */
        private function handleStream(callable $callback): void
        {
            if (ob_get_level())
            {
                ob_end_flush();
            }

            while (!connection_aborted())
            {
                $output = $callback();
                
                if ($output === false || $output === null)
                {
                    break;
                }

                print($output);
                flush();
            }
        }
    }
