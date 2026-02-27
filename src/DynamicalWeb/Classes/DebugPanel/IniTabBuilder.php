<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class IniTabBuilder
    {
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
                $display = $value === null ? '—' : Shared::escape((string) $value);
                $groups[$group][Shared::escape($key)] = $display;
            }

            ksort($groups);

            $html = '';
            foreach ($groups as $group => $entries)
            {
                $html .= Shared::buildSection(Shared::escape($group) . ' (' . count($entries) . ')', Shared::buildParametersHtml($entries));
            }

            return $html;
        }
    }
