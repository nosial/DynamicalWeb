<?php

    namespace DynamicalWeb\Objects\WebSocket;

    use DynamicalWeb\Interfaces\SerializableInterface;

    class ConnectionState implements SerializableInterface
    {
        private bool $connected;
        private bool $closed;
        private bool $timedOut;
        private int $bytesSent;
        private int $bytesReceived;

        /**
         * Public Constructor
         *
         * @param array $array The array to construct the ConnectionState from
         */
        public function __construct(array $array)
        {
            $this->connected = $array['connected'] ?? false;
            $this->closed = $array['closed'] ?? false;
            $this->timedOut = $array['timed_out'] ?? false;
            $this->bytesSent = $array['bytes_sent'] ?? 0;
            $this->bytesReceived = $array['bytes_received'] ?? 0;
        }

        /**
         * Check if the connection is currently established.
         *
         * @return bool True if the connection is established, false otherwise.
         */
        public function isConnected(): bool
        {
            return $this->connected;
        }

        /**
         * Check if the connection has been closed.
         *
         * @return bool True if the connection is closed, false otherwise.
         */
        public function isClosed(): bool
        {
            return $this->closed;
        }

        /**
         * Check if the connection has timed out.
         *
         * @return bool True if the connection has timed out, false otherwise.
         */
        public function isTimedOut(): bool
        {
            return $this->timedOut;
        }

        /**
         * Get the total number of bytes sent to the client.
         *
         * @return int The total number of bytes sent.
         */
        public function getBytesSent(): int
        {
            return $this->bytesSent;
        }

        /**
         * Get the total number of bytes received from the client.
         *
         * @return int The total number of bytes received.
         */
        public function getBytesReceived(): int
        {
            return $this->bytesReceived;
        }

        /**
         * @inheritDoc
         */
        public function toArray(): array
        {
            return [
                'connected' => $this->connected,
                'closed' => $this->closed,
                'timed_out' => $this->timedOut,
                'bytes_sent' => $this->bytesSent,
                'bytes_received' => $this->bytesReceived
            ];
        }

        /**
         * @inheritDoc
         */
        public static function fromArray(array $array): ConnectionState
        {
            return new self($array);
        }
    }