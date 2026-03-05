<?php

    namespace DynamicalWeb\Classes;

    class Logger
    {
        private static ?\LogLib2\Logger $logger=null;

        /**
         * Retrieves the logger instance for the DynamicalWeb framework.
         *
         * @return \LogLib2\Logger Returns the logger instance.
         */
        public static function getLogger(): \LogLib2\Logger
        {
            if(self::$logger === null)
            {
                self::$logger = new \LogLib2\Logger('net.nosial.dynamicalweb');
            }

            return self::$logger;
        }
    }