<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;

    class ExtensionsTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            return self::buildSection('Loaded Extensions (' . count(get_loaded_extensions()) . ')', PhpTabBuilder::buildPhpExtensionsHtml());
        }
    }
