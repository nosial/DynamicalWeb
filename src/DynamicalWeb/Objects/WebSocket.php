<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Exceptions\WebSocketException;
    use DynamicalWeb\Objects\WebSocket\ConnectionDetails;
    use DynamicalWeb\Objects\WebSocket\ConnectionState;

    class WebSocket
    {
        private ?ConnectionDetails $connection;
        /**
         * @var resource|null
         */
        private $socket = null;
        private bool $connected = false;
        private bool $closed = false;
        private int $bytesSent = 0;
        private int $bytesReceived = 0;
        private int $readTimeoutSec = 0;
        private int $readTimeoutUsec = 50000;
        private int $maxPayloadSize = 0;

        /**
         * WebSocket constructor.
         *
         * Initializes the WebSocket connection using environment variables. If the required environment variables are not set, an exception is thrown.
         * @throws WebSocketException if the connection details cannot be obtained from the environment or if the connection fails.
         */
        public function __construct()
        {
            $this->connection = ConnectionDetails::fromEnvironment();

            if ($this->connection === null)
            {
                throw new WebSocketException(
                    'WebsocketLib: WSS_ENABLED environment variable is not set to "1". This library must be executed within a WebSocket Server PHP process.'
                );
            }

            $this->connect();
        }

        /**
         * Establishes a TCP connection to the WebSocket server using the connection details obtained from the
         * environment. If the connection fails, an exception is thrown.
         *
         * @throws WebSocketException if the TCP host or port is invalid or if the connection fails.
         */
        private function connect(): void
        {
            $host = $this->connection->getTcpHost();
            $port = $this->connection->getTcpPort();

            if ($host === '' || $port <= 0)
            {
                throw new WebSocketException('Invalid TCP host or port from environment');
            }

            $errno = 0;
            $errstr = '';
            $this->socket = @fsockopen($host, $port, $errno, $errstr, 30);

            if ($this->socket === false)
            {
                $this->socket = null;
                throw new WebSocketException("Failed to connect to {$host}:{$port} - {$errstr} ({$errno})");
            }

            $this->applySocketTimeout();
            $this->connected = true;
            $this->closed = false;

            $connId = $this->connection->getConnectionId();
            if ($connId !== '')
            {
                @fwrite($this->socket, $connId . "\n");
                @fflush($this->socket);
            }
        }

        /**
         * Sets the read timeout for the WebSocket connection.
         *
         * @param float $seconds The read timeout in seconds (can be a fractional value).
         */
        public function setTimeout(float $seconds): void
        {
            $this->readTimeoutSec = (int)$seconds;
            $this->readTimeoutUsec = (int)(($seconds - $this->readTimeoutSec) * 1000000);
            $this->applySocketTimeout();
        }

        /**
         * Applies the current read timeout settings to the socket connection.
         *
         * This method is called internally whenever the read timeout is updated to ensure that the new timeout values are applied to the socket.
         */
        private function applySocketTimeout(): void
        {
            if ($this->socket !== null)
            {
                stream_set_timeout($this->socket, $this->readTimeoutSec, $this->readTimeoutUsec);
            }
        }

        /**
         * Sets the maximum payload size for sending and receiving data over the WebSocket connection.
         *
         * @param int $bytes The maximum payload size in bytes. A value of 0 means no limit.
         */
        public function setMaxPayloadSize(int $bytes): void
        {
            $this->maxPayloadSize = $bytes;
        }

        /**
         * Gets the current connection details for this WebSocket connection.
         *
         * @return ConnectionDetails|null The connection details, or null if the connection details could not be obtained from the environment.
         */
        public function getConnection(): ?ConnectionDetails
        {
            return $this->connection;
        }

        /**
         * Gets the metadata associated with the WebSocket connection.
         *
         * @return array An associative array containing the connection metadata, or an empty array if the connection details are not available.
         */
        public function getMetadata(): array
        {
            if ($this->connection === null)
            {
                return [];
            }

            return $this->connection->toArray();
        }

        /**
         * Sends data over the WebSocket connection.
         *
         * @param string $data The data to send.
         * @param int $chunkSize The size of each chunk to send (default is 65536 bytes).
         * @return bool True if the data was sent successfully, false otherwise.
         */
        public function send(string $data, int $chunkSize = 65536): bool
        {
            if (!$this->isConnected())
            {
                return false;
            }

            $total = strlen($data);

            if ($this->maxPayloadSize > 0 && $total > $this->maxPayloadSize)
            {
                return false;
            }

            $sent = 0;

            while ($sent < $total)
            {
                $remaining = $total - $sent;
                $writeSize = $remaining < $chunkSize ? $remaining : $chunkSize;
                $chunk = $sent === 0 && $writeSize === $total ? $data : substr($data, $sent, $writeSize);

                $written = @fwrite($this->socket, $chunk);
                if ($written === false || $written === 0)
                {
                    $this->connected = false;
                    return false;
                }

                $sent += $written;
            }

            @fflush($this->socket);
            $this->bytesSent += $total;
            return true;
        }

        /**
         * Reads data from the WebSocket connection.
         *
         * @param int $length The maximum number of bytes to read (default is 8192 bytes).
         * @return string|null The data read from the connection, or null if the connection is closed or an error occurs.
         */
        public function read(int $length = 8192): ?string
        {
            if (!$this->isConnected())
            {
                return null;
            }

            if ($this->maxPayloadSize > 0 && $length > $this->maxPayloadSize)
            {
                $length = $this->maxPayloadSize;
            }

            $data = @fread($this->socket, $length);

            if ($data === false || $data === '')
            {
                if (feof($this->socket))
                {
                    $this->connected = false;
                }
                return null;
            }

            $this->bytesReceived += strlen($data);
            return $data;
        }

        /**
         * Reads data from the WebSocket connection in chunks until the specified conditions are met.
         *
         * @param int $chunkSize The size of each chunk to read (default is 65536 bytes).
         * @param int $maxLength The maximum total length of data to read (default is 0, which means no limit).
         * @param float $idleTimeout The maximum idle time in seconds before stopping the read operation (default is 0, which means no timeout).
         * @return string|null The data read from the connection, or null if the connection is closed or an error occurs.
         */
        public function readAll(int $chunkSize = 65536, int $maxLength = 0, float $idleTimeout = 0): ?string
        {
            if (!$this->isConnected())
            {
                return null;
            }

            if ($this->maxPayloadSize > 0 && ($maxLength === 0 || $maxLength > $this->maxPayloadSize))
            {
                $maxLength = $this->maxPayloadSize;
            }

            $result = '';
            $totalRead = 0;
            $idleTimer = 0;

            while (true)
            {
                if ($maxLength > 0 && $totalRead >= $maxLength)
                {
                    break;
                }

                if ($idleTimeout > 0 && $idleTimer >= $idleTimeout)
                {
                    break;
                }

                $remaining = $maxLength > 0 ? $maxLength - $totalRead : $chunkSize;
                $readSize = $remaining < $chunkSize ? $remaining : $chunkSize;

                $data = @fread($this->socket, $readSize);

                if ($data === false)
                {
                    $this->connected = false;
                    break;
                }

                if ($data === '')
                {
                    if (feof($this->socket))
                    {
                        $this->connected = false;
                        break;
                    }

                    $idleTimer += 0.05;
                    usleep(50000);
                    continue;
                }

                $result .= $data;
                $totalRead += strlen($data);
                $idleTimer = 0;

                if ($this->maxPayloadSize > 0 && $totalRead > $this->maxPayloadSize)
                {
                    break;
                }
            }

            $this->bytesReceived += strlen($result);
            return $result === '' ? null : $result;
        }

        /**
         * Sends data over the WebSocket connection and waits for a response.
         *
         * @param string $data The data to send.
         * @param float $timeout The maximum time in seconds to wait for a response (default is 5.0 seconds).
         * @param int $chunkSize The size of each chunk to read when waiting for a response (default is 8192 bytes).
         * @return string|null The response received from the connection, or null if the connection is closed, an error occurs, or the timeout is reached.
         */
        public function sendAndReceive(string $data, float $timeout = 5.0, int $chunkSize = 8192): ?string
        {
            if (!$this->send($data))
            {
                return null;
            }

            @fflush($this->socket);
            return $this->readWithSelect($timeout, $chunkSize);
        }

        /**
         * Reads data from the WebSocket connection using stream_select to wait for data to become available.
         *
         * @param float $timeout The maximum time in seconds to wait for data (default is 5.0 seconds).
         * @param int $chunkSize The size of each chunk to read when data becomes available (default is 8192 bytes).
         * @return string|null The data read from the connection, or null if the connection is closed, an error occurs, or the timeout is reached.
         */
        private function readWithSelect(float $timeout, int $chunkSize): ?string
        {
            $result = '';
            $remaining = $timeout;
            $noTimeout = $timeout == 0;

            while (true)
            {
                if (!$this->isConnected())
                {
                    break;
                }

                $read = [$this->socket];
                $write = null;
                $except = null;

                if ($noTimeout)
                {
                    $seconds = 0;
                    $microseconds = 100000;
                }
                else
                {
                    if ($remaining <= 0)
                    {
                        break;
                    }
                    $seconds = $remaining < 1 ? 0 : (int)$remaining;
                    $microseconds = (int)(($remaining - $seconds) * 1000000);
                }

                $available = @stream_select($read, $write, $except, $seconds, $microseconds);

                if ($available === false)
                {
                    $this->connected = false;
                    break;
                }

                if ($available > 0)
                {
                    $data = @fread($this->socket, $chunkSize);
                    if ($data === false || $data === '')
                    {
                        if (feof($this->socket))
                        {
                            $this->connected = false;
                        }
                        break;
                    }
                    $result .= $data;
                    $this->bytesReceived += strlen($data);

                    if ($this->maxPayloadSize > 0 && strlen($result) > $this->maxPayloadSize)
                    {
                        break;
                    }

                    if (!$noTimeout)
                    {
                        $remaining = $timeout;
                    }
                }
                elseif (!$noTimeout)
                {
                    $remaining -= $seconds + ($microseconds / 1000000);
                }
            }

            return $result === '' ? null : $result;
        }

        /**
         * Reads a single line of data from the WebSocket connection.
         *
         * @return string|null The line read from the connection, or null if the connection is closed or an error occurs.
         */
        public function readLine(): ?string
        {
            if (!$this->isConnected())
            {
                return null;
            }

            $data = @fgets($this->socket);

            if ($data === false || $data === '')
            {
                if (feof($this->socket))
                {
                    $this->connected = false;
                }
                return null;
            }

            $this->bytesReceived += strlen($data);
            return rtrim($data, "\r\n");
        }

        /**
         * Checks if the WebSocket connection is currently active and valid.
         *
         * @return bool True if the connection is active and valid, false otherwise.
         */
        public function isConnected(): bool
        {
            if ($this->closed || $this->socket === null)
            {
                return false;
            }

            $metadata = @stream_get_meta_data($this->socket);
            if ($metadata === false)
            {
                $this->connected = false;
                return false;
            }

            if ($metadata['timed_out'] || feof($this->socket))
            {
                $this->connected = false;
                return false;
            }

            return $this->connected;
        }

        /**
         * Returns the underlying socket resource for this WebSocket connection.
         *
         * @return resource|null The socket resource, or null if the connection is closed.
         */
        public function getSocket()
        {
            return $this->socket;
        }

        /**
         * Gets the current state of the WebSocket connection, including connection status, timeout status, and byte counts.
         *
         * @return ConnectionState An object representing the current state of the WebSocket connection state
         */
        public function getState(): ConnectionState
        {
            $timedOut = false;
            if ($this->socket !== null)
            {
                $metadata = @stream_get_meta_data($this->socket);
                if (is_array($metadata))
                {
                    $timedOut = !empty($metadata['timed_out']);
                }
            }

            return ConnectionState::fromArray([
                'connected' => $this->connected && !$timedOut && !$this->closed,
                'closed' => $this->closed,
                'timed_out' => $timedOut,
                'bytes_sent' => $this->bytesSent,
                'bytes_received' => $this->bytesReceived,
            ]);
        }

        /**
         * Gets the total number of bytes sent over this WebSocket connection.
         *
         * @return int The total number of bytes sent.
         */
        public function getBytesSent(): int
        {
            return $this->bytesSent;
        }

        /**
         * Gets the total number of bytes received over this WebSocket connection.
         *
         * @return int The total number of bytes received.
         */
        public function getBytesReceived(): int
        {
            return $this->bytesReceived;
        }

        /**
         * Closes the WebSocket connection and releases any associated resources.
         *
         * After calling this method, the connection will be marked as closed and any attempts to read from or write to the connection will fail.
         */
        public function close(): void
        {
            $this->closed = true;
            $this->connected = false;

            if ($this->socket !== null)
            {
                @fclose($this->socket);
                $this->socket = null;
            }
        }


        /**
         * WebSocket destructor.
         *
         * Ensures that the WebSocket connection is properly closed when the object is destroyed.
         */
        public function __destruct()
        {
            $this->close();
        }
    }
