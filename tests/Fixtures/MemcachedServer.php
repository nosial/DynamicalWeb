<?php

    namespace DynamicalWeb\Tests\Fixtures;

    use Memcached;

    /**
     * Starts a throwaway memcached server on a free local port for tests.
     */
    class MemcachedServer
    {
        /** @var resource|null */
        private static $process = null;
        private static ?int $port = null;

        /**
         * Starts the server if it is not running yet.
         *
         * @return int|null The port, or null when the memcached extension or binary is unavailable
         */
        public static function start(): ?int
        {
            if (self::$port !== null)
            {
                return self::$port;
            }

            if (!class_exists(Memcached::class))
            {
                return null;
            }

            $binary = trim((string)shell_exec('command -v memcached 2>/dev/null'));
            if ($binary === '')
            {
                return null;
            }

            // Grab a free port, then start the server on it
            $socket = stream_socket_server('tcp://127.0.0.1:0');
            $port = (int)substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
            fclose($socket);

            $process = proc_open(
                [$binary, '-l', '127.0.0.1', '-p', (string)$port, '-U', '0', '-m', '16'],
                [['file', '/dev/null', 'r'], ['file', '/dev/null', 'w'], ['file', '/dev/null', 'w']],
                $pipes
            );

            if (!is_resource($process))
            {
                return null;
            }

            for ($i = 0; $i < 50; $i++)
            {
                $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.1);
                if ($connection !== false)
                {
                    fclose($connection);
                    self::$process = $process;
                    self::$port = $port;
                    register_shutdown_function([self::class, 'stop']);
                    return $port;
                }

                usleep(100000);
            }

            proc_terminate($process);
            proc_close($process);
            return null;
        }

        /**
         * Stops the server.
         */
        public static function stop(): void
        {
            if (self::$process !== null)
            {
                proc_terminate(self::$process);
                proc_close(self::$process);
                self::$process = null;
                self::$port = null;
            }
        }

        /**
         * Removes every entry from the server.
         */
        public static function flush(): void
        {
            self::client()?->flush();
        }

        /**
         * Returns a client connected to the server without any key prefix.
         *
         * @return Memcached|null The client, or null when the server is not running
         */
        public static function client(): ?Memcached
        {
            if (self::$port === null)
            {
                return null;
            }

            $client = new Memcached();
            $client->addServer('127.0.0.1', self::$port);
            return $client;
        }
    }
