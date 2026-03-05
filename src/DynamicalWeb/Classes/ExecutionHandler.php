<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Exceptions\ExecutionException;
    use Throwable;

    class ExecutionHandler
    {
        /**
         * Executes a PHTML file with output buffering
         *
         * @param string $filePath The full path to the PHTML file
         * @return string The buffered output
         * @throws ExecutionException If execution fails
         */
        public static function executePhtml(string $filePath): string
        {
            if (!is_file($filePath) || !is_readable($filePath))
            {
                throw new ExecutionException(sprintf('File "%s" does not exist or is not readable', basename($filePath)));
            }

            $initialObLevel  = ob_get_level();
            $previousHandler = set_exception_handler(null);
            $startTime       = microtime(true);
            ob_start();

            try
            {
                include $filePath;
                $output = ob_get_clean();

                if ($output === false)
                {
                    throw new ExecutionException(sprintf('Failed to retrieve output buffer for file "%s"', basename($filePath)));
                }
            }
            catch (Throwable $e)
            {
                while (ob_get_level() > $initialObLevel)
                {
                    ob_end_clean();
                }

                throw new ExecutionException(sprintf('Failed to execute file "%s": %s', basename($filePath), $e->getMessage()), 0, $e);
            }
            finally
            {
                DebugPanel::trackFileExecution($filePath, 'phtml', microtime(true) - $startTime);

                if ($previousHandler !== null)
                {
                    set_exception_handler($previousHandler);
                }
            }

            return $output;
        }

        /**
         * Executes a PHP script without output buffering
         *
         * @param string $filePath The full path to the PHP file
         * @throws ExecutionException If execution fails
         */
        public static function executePhp(string $filePath): void
        {
            if (!is_file($filePath) || !is_readable($filePath))
            {
                throw new ExecutionException(sprintf('PHP file "%s" does not exist or is not readable', basename($filePath)));
            }

            $previousHandler = set_exception_handler(null);
            $startTime       = microtime(true);

            try
            {
                include $filePath;
            }
            catch (Throwable $e)
            {
                throw new ExecutionException(sprintf('Failed to execute PHP file "%s": %s', basename($filePath), $e->getMessage()), 0, $e);
            }
            finally
            {
                DebugPanel::trackFileExecution($filePath, 'php', microtime(true) - $startTime);

                if ($previousHandler !== null)
                {
                    set_exception_handler($previousHandler);
                }
            }
        }
    }
