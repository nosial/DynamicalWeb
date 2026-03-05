<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;

    class IniTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $all = ini_get_all(null, false);
            if (empty($all))
            {
                return '';
            }

            ksort($all);

            $groups = [];
            foreach ($all as $key => $value)
            {
                $dot   = strpos($key, '.');
                $group = $dot !== false ? substr($key, 0, $dot) : 'core';
                $display = $value === null ? '—' : self::escape((string) $value);
                $groups[$group][self::escape($key)] = $display;
            }

            ksort($groups);

            $html = '';
            foreach ($groups as $group => $entries)
            {
                $html .= self::buildSection(self::escape($group) . ' (' . count($entries) . ')', self::buildParametersHtml($entries));
            }

            return $html;
        }
    }
