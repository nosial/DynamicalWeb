<?php

    namespace DynamicalWeb\Objects\WebSocket;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class ConnectionDetails implements SerializableInterface
    {
        private string $connectionId;
        private string $clientIp;
        private int $clientPort;
        private ?string $serverHost;
        private ?int $serverPort;
        private string $requestUri;
        private ?string $requestPath;
        private ?string $requestQuery;
        private array $requestHeaders;
        private ?string $protocol;
        private ?string $version;
        private ?string $origin;
        private ?string $userAgent;
        private ?string $host;
        private ?string $xForwardedFor;
        private ?string $xRealIp;
        private string $tcpHost;
        private int $tcpPort;

        /**
         * Public Constructor
         *
         * @param array $data An associative array containing the connection data
         */
        public function __construct(array $data)
        {
            $this->connectionId = $data['connection_id'] ?? '';
            $this->clientIp = $data['client_ip'] ?? '';
            $this->clientPort = (int)($data['client_port'] ?? 0);
            $this->serverHost = $data['server_host'] ?? null;
            $this->serverPort = isset($data['server_port']) ? (int)$data['server_port'] : null;
            $this->requestUri = $data['request_uri'] ?? '';
            $this->requestPath = $data['request_path'] ?? null;
            $this->requestQuery = $data['request_query'] ?? null;
            $this->requestHeaders = $data['request_headers'] ?? [];
            $this->protocol = $data['protocol'] ?? null;
            $this->version = $data['version'] ?? null;
            $this->origin = $data['origin'] ?? null;
            $this->userAgent = $data['user_agent'] ?? null;
            $this->host = $data['host'] ?? null;
            $this->xForwardedFor = $data['x_forwarded_for'] ?? null;
            $this->xRealIp = $data['x_real_ip'] ?? null;
            $this->tcpHost = $data['tcp_host'] ?? '';
            $this->tcpPort = (int)($data['tcp_port'] ?? 0);
        }

        /**
         * Create a WebSocketConnection instance from environment variables
         *
         * @return ConnectionDetails|null Returns a WebSocketConnection instance if the environment variables indicate an active connection, or null otherwise
         */
        public static function fromEnvironment(): ?self
        {
            if (getenv('WSS_ENABLED') !== '1')
            {
                return null;
            }

            $headers = [];
            $rawHeaders = getenv('WSS_REQUEST_HEADERS');
            if ($rawHeaders !== false && $rawHeaders !== '')
            {
                $decoded = json_decode($rawHeaders, true);
                if (is_array($decoded))
                {
                    $headers = $decoded;
                }
            }

            return new self([
                'connection_id' => getenv('WSS_CONNECTION_ID') ?: '',
                'client_ip' => getenv('WSS_CLIENT_IP') ?: '',
                'client_port' => getenv('WSS_CLIENT_PORT') ?: 0,
                'server_host' => getenv('WSS_SERVER_HOST') ?: null,
                'server_port' => getenv('WSS_SERVER_PORT') ?: null,
                'request_uri' => getenv('WSS_REQUEST_URI') ?: '',
                'request_path' => getenv('WSS_REQUEST_PATH') ?: null,
                'request_query' => getenv('WSS_REQUEST_QUERY') ?: null,
                'request_headers' => $headers,
                'protocol' => getenv('WSS_SEC_WEBSOCKET_PROTOCOL') ?: null,
                'version' => getenv('WSS_SEC_WEBSOCKET_VERSION') ?: null,
                'origin' => getenv('WSS_ORIGIN') ?: null,
                'user_agent' => getenv('WSS_USER_AGENT') ?: null,
                'host' => getenv('WSS_HOST') ?: null,
                'x_forwarded_for' => getenv('WSS_X_FORWARDED_FOR') ?: null,
                'x_real_ip' => getenv('WSS_X_REAL_IP') ?: null,
                'tcp_host' => getenv('WSS_TCP_HOST') ?: '',
                'tcp_port' => getenv('WSS_TCP_PORT') ?: 0,
            ]);
        }

        /**
         * Returns the unique identifier for this WebSocket connection.
         *
         * @return string The connection ID
         */
        public function getConnectionId(): string
        {
            return $this->connectionId;
        }

        /**
         * Returns the client's IP address for this WebSocket connection.
         *
         * @return string The client's IP address
         */
        public function getClientIp(): string
        {
            return $this->clientIp;
        }

        /**
         * Returns the client's port number for this WebSocket connection.
         *
         * @return int The client's port number
         */
        public function getClientPort(): int
        {
            return $this->clientPort;
        }

        /**
         * Returns the server's host name for this WebSocket connection, or null if not available.
         *
         * @return string|null The server's host name, or null if not available
         */
        public function getServerHost(): ?string
        {
            return $this->serverHost;
        }

        /**
         * Returns the server's port number for this WebSocket connection, or null if not available.
         *
         * @return int|null The server's port number, or null if not available
         */
        public function getServerPort(): ?int
        {
            return $this->serverPort;
        }

        /**
         * Returns the full request URI for this WebSocket connection.
         *
         * @return string The full request URI
         */
        public function getRequestUri(): string
        {
            return $this->requestUri;
        }

        /**
         * Returns the request path for this WebSocket connection, or null if not available.
         *
         * @return string|null The request path, or null if not available
         */
        public function getRequestPath(): ?string
        {
            return $this->requestPath;
        }

        /**
         * Returns the request query string for this WebSocket connection, or null if not available.
         *
         * @return string|null The request query string, or null if not available
         */
        public function getRequestQuery(): ?string
        {
            return $this->requestQuery;
        }

        /**
         * Returns an associative array of the request headers for this WebSocket connection.
         *
         * @return array An associative array of the request headers
         */
        public function getRequestHeaders(): array
        {
            return $this->requestHeaders;
        }

        /**
         * Returns the WebSocket subprotocol requested by the client for this connection, or null if not specified.
         *
         * @return string|null The WebSocket subprotocol, or null if not specified
         */
        public function getProtocol(): ?string
        {
            return $this->protocol;
        }

        /**
         * Returns the WebSocket version requested by the client for this connection, or null if not specified.
         *
         * @return string|null The WebSocket version, or null if not specified
         */
        public function getVersion(): ?string
        {
            return $this->version;
        }

        /**
         * Returns the value of the Origin header sent by the client for this WebSocket connection, or null if not specified.
         *
         * @return string|null The value of the Origin header, or null if not specified
         */
        public function getOrigin(): ?string
        {
            return $this->origin;
        }

        /**
         * Returns the value of the User-Agent header sent by the client for this WebSocket connection, or null if not specified.
         *
         * @return string|null The value of the User-Agent header, or null if not specified
         */
        public function getUserAgent(): ?string
        {
            return $this->userAgent;
        }

        /**
         * Returns the value of the Host header sent by the client for this WebSocket connection, or null if not specified.
         *
         * @return string|null The value of the Host header, or null if not specified
         */
        public function getHost(): ?string
        {
            return $this->host;
        }

        /**
         * Returns the value of the X-Forwarded-For header sent by the client for this WebSocket connection, or null if not specified.
         *
         * @return string|null The value of the X-Forwarded-For header, or null if not specified
         */
        public function getXForwardedFor(): ?string
        {
            return $this->xForwardedFor;
        }

        /**
         * Returns the value of the X-Real-IP header sent by the client for this WebSocket connection, or null if not specified.
         *
         * @return string|null The value of the X-Real-IP header, or null if not specified
         */
        public function getXRealIp(): ?string
        {
            return $this->xRealIp;
        }

        /**
         * Returns the TCP host address for this WebSocket connection.
         *
         * @return string The TCP host address
         */
        public function getTcpHost(): string
        {
            return $this->tcpHost;
        }

        /**
         * Returns the TCP port number for this WebSocket connection.
         *
         * @return int The TCP port number
         */
        public function getTcpPort(): int
        {
            return $this->tcpPort;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'connection_id' => $this->connectionId,
                'client_ip'        => $this->clientIp,
                'client_port'      => $this->clientPort,
                'server_host'      => $this->serverHost,
                'server_port'      => $this->serverPort,
                'request_uri'      => $this->requestUri,
                'request_path'     => $this->requestPath,
                'request_query'    => $this->requestQuery,
                'request_headers'  => $this->requestHeaders,
                'protocol'         => $this->protocol,
                'version'          => $this->version,
                'origin'           => $this->origin,
                'user_agent'       => $this->userAgent,
                'host'             => $this->host,
                'x_forwarded_for'  => $this->xForwardedFor,
                'x_real_ip'        => $this->xRealIp,
                'tcp_host'         => $this->tcpHost,
                'tcp_port'         => $this->tcpPort,
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
