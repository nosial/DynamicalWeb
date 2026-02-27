<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class ConstantsTabBuilder
    {
        public static function build(): string
        {
            return PhpTabBuilder::buildPhpUserConstantsSection();
        }
    }
