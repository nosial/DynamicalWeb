<?php

    namespace DynamicalWeb\Html;

    use DynamicalWeb\Classes\DebugPanel;
    use DynamicalWeb\Classes\ExecutionHandler;
    use DynamicalWeb\Exceptions\ExecutionException;
    use DynamicalWeb\Exceptions\LocaleException;
    use DynamicalWeb\Exceptions\SectionException;
    use DynamicalWeb\Interfaces\StringInterface;
    use DynamicalWeb\WebSession;

    class Functions
    {
        /** Tracks the locale_id in scope during execution; context-switched by insertSection(). */
        private static ?string $activeLocaleSection=null;

        /**
         * Prints out the given input, optionally escapes the input so
         *
         * @param mixed $text The text to print
         * @param bool $escape if True, escapes the HTML encoding from the text
         */
        public static function print(mixed $text, bool $escape=true): void
        {
            // Support for classes/types that cannot be cast to string directly but implement the StringInterface
            if($text instanceof StringInterface)
            {
                $text = $text->toString();
            }

            if($escape)
            {
                $text = htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }

            print((string)$text);
        }

        /**
         * Alias for the Print method with locale functionality
         *
         * @param mixed $id The ID of the locale string to print
         * @param array $locale Optional, an associative array of parameters to replace in the locale string (e.g. ['name' => 'John'] to replace {name} in the locale string)
         * @param bool $escape if True, escapes the HTML encoding from the locale string
         * @throws LocaleException Thrown if there was an error loading the locale or if the locale key was not found
         */
        public static function printl(mixed $id, array $locale=[], bool $escape=true): void
        {
            $currentLocale = WebSession::getLocale();
            if ($currentLocale === null)
            {
                throw new LocaleException('No locale is loaded for the current session');
            }

            // Section context takes priority; fall back to the current route's locale_id
            $localeId = self::$activeLocaleSection ?? WebSession::getCurrentRoute()?->getLocaleId();
            if ($localeId === null)
            {
                throw new LocaleException('No active locale section is set for the current execution context');
            }

            $string = $currentLocale->getString($localeId, (string)$id, $locale);
            if ($string === null)
            {
                throw new LocaleException(sprintf('Locale key "%s" not found in locale "%s" for locale_id "%s"', $id, $currentLocale->getLocaleCode(), $localeId));
            }

            self::print($string, $escape);
        }

        /**
         * Renders a named section (a reusable .phtml fragment) and outputs its content.
         *
         * The section's locale_id (if configured) overrides the current route's locale_id for
         * the duration of the section's rendering, so printl() calls inside the section
         * resolve strings from the section's own locale scope without conflict.
         *
         * @param string $name The section name as defined under `sections:` in the web configuration.
         * @throws ExecutionException If executing the section throws an error.
         * @throws SectionException If the section is not configured or its module file is not found.
         */
        public static function insertSection(string $name): void
        {
            $instance = WebSession::getInstance();
            if ($instance === null)
            {
                throw new SectionException(sprintf('Cannot insert section "%s": no active web session', $name));
            }

            $section = $instance->getSection($name);
            if ($section === null)
            {
                throw new SectionException(sprintf('Section "%s" is not defined in the web configuration', $name));
            }

            $modulePath = $instance->getSectionModulePath($name);
            if ($modulePath === null || !file_exists($modulePath))
            {
                throw new SectionException(sprintf('Module file for section "%s" not found at "%s"', $name, $modulePath ?? 'unknown'));
            }

            // Push this section's locale_id onto the context stack; restore after execution
            $previous  = self::$activeLocaleSection;
            $startTime = microtime(true);
            self::$activeLocaleSection = $section->getLocaleId();

            try
            {
                print(ExecutionHandler::executePhtml($modulePath));
            }
            finally
            {
                $duration = microtime(true) - $startTime;
                self::$activeLocaleSection = $previous;
                DebugPanel::trackSectionExecution($name, $duration);
            }
        }
    }

