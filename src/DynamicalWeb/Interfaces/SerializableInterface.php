<?php

    namespace DynamicalWeb\Interfaces;

    interface SerializableInterface
    {
        /**
         * Converts the instance of the implementing class to an associative array.
         *
         * @return array Returns an associative array representation of the instance.
         */
        public function toArray(): array;

        /**
         * Creates an instance of the implementing class from an associative array.
         *
         * @param array $array The associative array containing the data to create the instance.
         * @return SerializableInterface Returns an instance of the implementing class.
         */
        public static function fromArray(array $array): SerializableInterface;
    }