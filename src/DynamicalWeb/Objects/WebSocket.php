<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Exceptions\WebSocketException;
    use DynamicalWeb\Objects\WebSocket\ConnectionDetails;
    use DynamicalWeb\Objects\WebSocket\ConnectionState;

    class WebSocket
    {
        private ?ConnectionDetails $connection;
        private $socket = null;
        private bool $connected = false;
        private bool $closed = false;
        private int $bytesSent = 0;
        private int $bytesReceived = 0;
        private ?int $readTimeoutSec = null;
        private ?int $readTimeoutUsec = null;
        private int $maxPayloadSize = 0;
        private int $connectTimeout = 30;

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

        public function setConnectTimeout(int $seconds): void
        {
            $this->connectTimeout = $seconds;
        }

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

        private function applySocketTimeout(): void
        {
            if ($this->socket !== null && $this->readTimeoutSec !== null)
            {
                stream_set_timeout($this->socket, $this->readTimeoutSec, $this->readTimeoutUsec);
            }
        }

        public function setMaxPayloadSize(int $bytes): void
        {
            $this->maxPayloadSize = $bytes;
        }

        public function getConnection(): ?ConnectionDetails
        {
            return $this->connection;
        }

        public function getMetadata(): array
        {
            if ($this->connection === null)
            {
                return [];
            }

            return $this->connection->toArray();
        }

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

        public function sendAndReceive(string $data, float $timeout = 5.0, int $chunkSize = 8192): ?string
        {
            if (!$this->send($data))
            {
                return null;
            }

            @fflush($this->socket);
            return $this->readWithSelect($timeout, $chunkSize);
        }

        public function sendAndReceiveAll(
            string $data,
            float $timeout = 5.0,
            int $chunkSize = 65536,
            int $maxLength = 0,
            float $idleTimeout = 0.5
        ): ?string
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

        public function getSocket()
        {
            return $this->socket;
        }

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

        public function getBytesSent(): int
        {
            return $this->bytesSent;
        }

        public function getBytesReceived(): int
        {
            return $this->bytesReceived;
        }

        public function disconnect(): void
        {
            $this->close();
        }

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

        public function __destruct()
        {
            if ($this->socket !== null)
            {
                @fclose($this->socket);
                $this->socket = null;
            }
        }
    }
