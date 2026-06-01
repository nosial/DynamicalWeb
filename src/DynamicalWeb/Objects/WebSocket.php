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
        private ?int $readTimeoutSec = null;
        private ?int $readTimeoutUsec = null;
        private int $maxPayloadSize = 0;
        private int $connectTimeout = 30;

        /**
         * WebSocket constructor.
         *
         * Initializes the WebSocket connection using environment variables. If the required environment variables
         * are not set, or if the connection to the TCP bridge fails, a WebSocketException is thrown.
         *
         * @throws WebSocketException if the WSS_ENABLED environment variable is not set to "1" or if the connection to
         *                            the TCP bridge fails.
         */
        public function __construct()
        {
            $this->connection = ConnectionDetails::fromEnvironment();

            if ($this->connection === null)
            {
                throw new WebSocketException(
                    'WSS_ENABLED environment variable is not set to "1". This library must be executed within a WebSocket Server PHP process.'
                );
            }

            $this->connect();
        }

        /**
         * Sets the connection timeout for establishing a connection to the TCP bridge.
         *
         * @param int $seconds The number of seconds to wait for a connection to be established before timing out.
         */
        public function setConnectTimeout(int $seconds): void
        {
            $this->connectTimeout = $seconds;
        }

        /**
         * Establishes a connection to the TCP bridge using the host and port specified in the environment variables.
         *
         * @throws WebSocketException if the TCP host or port is invalid, or if the connection to the TCP bridge fails.
         */
        private function connect(): void
        {
            $host = $this->connection->getTcpHost();
            $port = $this->connection->getTcpPort();

            if ($host === '' || $port <= 0)
            {
                throw new WebSocketException(
                    'Invalid TCP host or port from environment: host="' . $host . '", port=' . $port
                );
            }

            $errno = 0;
            $errstr = '';
            $this->socket = @fsockopen($host, $port, $errno, $errstr, $this->connectTimeout);

            if ($this->socket === false)
            {
                $this->socket = null;
                throw new WebSocketException(
                    "Failed to connect to TCP bridge {$host}:{$port} — {$errstr} ({$errno})"
                );
            }

            $this->applySocketTimeout();
            $this->connected = true;
            $this->closed = false;

            $connId = $this->connection->getConnectionId();
            if ($connId !== '')
            {
                $written = @fwrite($this->socket, $connId . "\n");
                if ($written === false || $written === 0)
                {
                    $this->close();
                    throw new WebSocketException(
                        "Failed to send connection ID to TCP bridge {$host}:{$port}"
                    );
                }
                @fflush($this->socket);
            }
        }

        /**
         * Sets the read timeout for socket operations. If the timeout is null or non-positive, no timeout will be applied.
         *
         * @param float|null $seconds The number of seconds to wait for data before timing out, or null to disable timeouts.
         */
        public function setTimeout(?float $seconds): void
        {
            if ($seconds === null || $seconds <= 0)
            {
                $this->readTimeoutSec = null;
                $this->readTimeoutUsec = null;
            }
            else
            {
                $this->readTimeoutSec = (int)$seconds;
                $this->readTimeoutUsec = (int)(($seconds - $this->readTimeoutSec) * 1000000);
            }

            $this->applySocketTimeout();
        }

        /**
         * Applies the current read timeout settings to the socket stream. This is called internally whenever the timeout
         * is updated or when a timeout occurs.
         */
        private function applySocketTimeout(): void
        {
            if ($this->socket !== null && $this->readTimeoutSec !== null)
            {
                stream_set_timeout($this->socket, $this->readTimeoutSec, $this->readTimeoutUsec);
            }
        }

        /**
         * Sets the maximum payload size for send and receive operations. If the payload size exceeds this limit, the
         * operation will fail.
         *
         * @param int $bytes The maximum payload size in bytes. Set to 0 for no limit.
         */
        public function setMaxPayloadSize(int $bytes): void
        {
            $this->maxPayloadSize = $bytes;
        }

        /**
         * Returns the connection details associated with this WebSocket connection, or null if the connection details
         * are not available.
         *
         * @return ConnectionDetails|null The connection details, or null if not available.
         */
        public function getConnection(): ?ConnectionDetails
        {
            return $this->connection;
        }

        /**
         * Returns an associative array of metadata about the WebSocket connection, including connection ID, client IP,
         * and client port.
         *
         * @return array An associative array containing connection metadata, or an empty array if the connection
         *               details are not available.
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
         * Sends data to the WebSocket client. If the connection is not established or if the data exceeds the maximum
         * payload size, the method returns false.
         *
         * @param string $data The data to send to the client.
         * @param int $chunkSize The size of each chunk to write to the socket (default: 65536 bytes). This can help with
         *                       large payloads.
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
         * Reads data from the WebSocket client. If the connection is not established, if a timeout occurs, or if the
         * end of the stream is reached, the method returns null.
         *
         * @param int $length The maximum number of bytes to read (default: 8192). If the maximum payload size is set and
         *                    is smaller than this value, it will be used instead.
         * @return string|null The data read from the client, or null if no data was read due to disconnection, timeout,
         *                     or end of stream.
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

            $read = [$this->socket];
            $write = null;
            $except = null;
            $available = @stream_select($read, $write, $except, $this->readTimeoutSec, $this->readTimeoutUsec);

            if ($available === false)
            {
                $this->connected = false;
                return null;
            }

            if ($available === 0)
            {
                $this->applySocketTimeout();
                return null;
            }

            $data = @fread($this->socket, $length);

            if ($data === false || $data === '')
            {
                if (feof($this->socket))
                {
                    $this->connected = false;
                    return null;
                }
                $this->applySocketTimeout();
                return null;
            }

            $this->bytesReceived += strlen($data);
            return $data;
        }

        /**
         * Reads data from the WebSocket client in chunks until the specified maximum length is reached, an idle timeout
         * occurs, or the connection is closed.
         *
         * @param int $chunkSize The size of each chunk to read from the socket (default: 65536 bytes).
         * @param int $maxLength The maximum total number of bytes to read. If 0, there is no limit (default: 0).
         * @param float $idleTimeout The maximum amount of time in seconds to wait for new data before considering the
         *                           connection idle and stopping the read operation (default: 0, which means no idle timeout).
         * @return string|null The data read from the client, or null if no data was read due to disconnection, timeout,
         *                     or end of stream.
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

                $read = [$this->socket];
                $write = null;
                $except = null;
                $available = @stream_select($read, $write, $except, 0, 50000);

                if ($available === false)
                {
                    $this->connected = false;
                    break;
                }

                if ($available === 0)
                {
                    $this->applySocketTimeout();
                    if ($idleTimeout > 0)
                    {
                        $idleTimer += 0.05;
                    }
                    usleep(50000);
                    continue;
                }

                $data = @fread($this->socket, $readSize);

                if ($data === false || $data === '')
                {
                    if (feof($this->socket))
                    {
                        $this->connected = false;
                        break;
                    }
                    $this->applySocketTimeout();
                    break;
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
         * Sends data to the WebSocket client and waits for a response. If the connection is not established, if a
         * timeout occurs, or if the end of the stream is reached, the method returns null.
         *
         * @param string $data The data to send to the client.
         * @param float $timeout The maximum amount of time in seconds to wait for a response from the client (default: 5.0 seconds).
         * @param int $chunkSize The size of each chunk to read from the socket when waiting for a response (default: 8192 bytes).
         * @return string|null The response data read from the client, or null if no response was received due to disconnection, timeout, or end of stream.
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
         * Sends data to the WebSocket client and reads the response in chunks until the specified maximum length is
         * reached, an idle timeout occurs, or the connection is closed.
         *
         * @param string $data The data to send to the client.
         * @param float $timeout The maximum amount of time in seconds to wait for a response from the client (default: 5.0 seconds).
         * @param int $chunkSize The size of each chunk to read from the socket when waiting for a response (default: 65536 bytes).
         * @param int $maxLength The maximum total number of bytes to read from the client. If 0, there is no limit (default: 0).
         * @return string|null The response data read from the client, or null if no response was received due to
         *                     disconnection, timeout, or end of stream.
         */
        public function sendAndReceiveAll(string $data, float $timeout = 5.0, int $chunkSize = 65536, int $maxLength = 0): ?string
        {
            if (!$this->send($data))
            {
                return null;
            }

            @fflush($this->socket);

            $result = '';
            $totalRead = 0;
            $deadline = microtime(true) + $timeout;

            while (true)
            {
                $remaining = microtime(true);
                if ($remaining >= $deadline)
                {
                    break;
                }

                if ($maxLength > 0 && $totalRead >= $maxLength)
                {
                    break;
                }

                $remainingLen = $maxLength > 0 ? $maxLength - $totalRead : $chunkSize;
                $readSize = $remainingLen < $chunkSize ? $remainingLen : $chunkSize;

                $read = [$this->socket];
                $write = null;
                $except = null;
                $timeLeft = max(0, $deadline - microtime(true));
                $sec = (int)$timeLeft;
                $usec = (int)(($timeLeft - $sec) * 1000000);

                $available = @stream_select($read, $write, $except, $sec, $usec);

                if ($available === false)
                {
                    $this->connected = false;
                    break;
                }

                if ($available === 0)
                {
                    $this->applySocketTimeout();
                    break;
                }

                $data = @fread($this->socket, $readSize);

                if ($data === false || $data === '')
                {
                    if (feof($this->socket))
                    {
                        $this->connected = false;
                        break;
                    }
                    $this->applySocketTimeout();
                    break;
                }

                $result .= $data;
                $totalRead += strlen($data);

                if ($this->maxPayloadSize > 0 && $totalRead > $this->maxPayloadSize)
                {
                    break;
                }
            }

            $this->bytesReceived += strlen($result);
            return $result === '' ? null : $result;
        }

        /**
         * Reads data from the WebSocket client using stream_select to wait for data availability. This method is used
         * internally by sendAndReceive() to read the response after sending data.
         *
         * @param float $timeout The maximum amount of time in seconds to wait for data from the client before timing out.
         * @param int $chunkSize The size of each chunk to read from the socket (default: 8192 bytes).
         * @return string|null The data read from the client, or null if no data was read due to disconnection, timeout,
         *                     or end of stream.
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
                            break;
                        }
                        $this->applySocketTimeout();
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
                else
                {
                    $this->applySocketTimeout();
                    if (!$noTimeout)
                    {
                        $remaining -= $seconds + ($microseconds / 1000000);
                    }
                }
            }

            return $result === '' ? null : $result;
        }

        /**
         * Reads a line of data from the WebSocket client. This method uses fgets to read until a newline character is
         * encountered. If the connection is not established, if a timeout occurs, or if the end of the stream is
         * reached, the method returns null.
         *
         * @return string|null The line of data read from the client, or null if no data was read due to disconnection,
         *                     timeout, or end of stream.
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

            if (!empty($metadata['timed_out']))
            {
                $this->applySocketTimeout();
                $metadata = @stream_get_meta_data($this->socket);
                if (!empty($metadata['timed_out']))
                {
                    $this->connected = false;
                    return false;
                }
            }

            if (feof($this->socket))
            {
                $this->connected = false;
                return false;
            }

            return $this->connected;
        }

        /**
         * Waits for data to be available on the socket for reading. This method uses stream_select to wait for data
         * availability.
         *
         * @param float $timeout The maximum amount of time in seconds to wait for data from the client before timing out.
         *                       If 0, it will wait indefinitely.
         * @return bool True if data is available for reading, false if a timeout occurred or if the connection is not established.
         */
        public function waitForData(float $timeout = 0): bool
        {
            if (!$this->isConnected())
            {
                return false;
            }

            $read = [$this->socket];
            $write = null;
            $except = null;

            $sec = $timeout > 0 ? (int)$timeout : 0;
            $usec = $timeout > 0 ? (int)(($timeout - $sec) * 1000000) : 100000;

            $available = @stream_select($read, $write, $except, $sec, $usec);

            if ($available === false)
            {
                $this->connected = false;
                return false;
            }

            return $available > 0;
        }

        /**
         * Returns the underlying socket resource for this WebSocket connection, or null if the socket is not available.
         *
         * @return resource|null The socket resource, or null if not available.
         */
        public function getSocket()
        {
            return $this->socket;
        }

        /**
         * Retrieves the current state of the WebSocket connection, including whether it is connected, closed, timed out,
         * and the number of bytes sent and received.
         *
         * @return ConnectionState An object representing the current state of the WebSocket connection.
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
         * Returns the total number of bytes sent to the WebSocket client since the connection was established.
         *
         * @return int The total number of bytes sent to the client.
         */
        public function getBytesSent(): int
        {
            return $this->bytesSent;
        }

        /**
         * Returns the total number of bytes received from the WebSocket client since the connection was established.
         *
         * @return int The total number of bytes received from the client.
         */
        public function getBytesReceived(): int
        {
            return $this->bytesReceived;
        }

        /**
         * Disconnects the WebSocket connection by closing the underlying socket and marking the connection as closed.
         * After calling this method, the WebSocket instance should not be used for sending or receiving data.
         */
        public function disconnect(): void
        {
            $this->close();
        }

        /**
         * Closes the WebSocket connection by closing the underlying socket and marking the connection as closed.
         * This method is idempotent and can be called multiple times without causing errors.
         */
        public function close(): void
        {
            if ($this->closed)
            {
                return;
            }

            $this->closed = true;
            $this->connected = false;

            if ($this->socket !== null)
            {
                @fclose($this->socket);
                $this->socket = null;
            }
        }

        /**
         * Destructor for the WebSocket class. Ensures that the socket is properly closed when the object is destroyed.
         */
        public function __destruct()
        {
            if ($this->socket !== null)
            {
                @fclose($this->socket);
                $this->socket = null;
            }
        }
    }
