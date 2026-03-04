<?php

    namespace DynamicalWeb\Interfaces;

    interface StringInterface
    {
        /**
         * Converts the instance of the implementing class to a string representation.
         *
         * @return string Returns a string representation of the instance.
         */
        public function toString(): string;
    }