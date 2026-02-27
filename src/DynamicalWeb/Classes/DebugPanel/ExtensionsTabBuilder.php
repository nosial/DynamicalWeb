<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class ExtensionsTabBuilder
    {
        public static function build(): string
        {
            return Shared::buildSection('Loaded Extensions (' . count(get_loaded_extensions()) . ')', PhpTabBuilder::buildPhpExtensionsHtml());
        }
    }
