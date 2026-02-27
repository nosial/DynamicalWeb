<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    class SessionTabBuilder
    {
        public static function build(): string
        {
            $status = session_status();
            $statusLabel = match($status)
            {
                PHP_SESSION_DISABLED => 'Disabled',
                PHP_SESSION_NONE     => 'Not Started',
                PHP_SESSION_ACTIVE   => 'Active',
                default              => 'Unknown',
            };

            $info = ['Status' => $statusLabel];

            if ($status !== PHP_SESSION_DISABLED)
            {
                $info['Session Name'] = Shared::escape(session_name());
                $info['Save Handler'] = Shared::escape(ini_get('session.save_handler') ?: 'files');
                $info['Save Path']    = Shared::escape(session_save_path() ?: ini_get('session.save_path') ?: 'Default');
                $info['GC Max Lifetime'] = ini_get('session.gc_maxlifetime') . 's';
                $info['Use Strict Mode'] = ini_get('session.use_strict_mode') ? 'Yes' : 'No';
            }

            if ($status === PHP_SESSION_ACTIVE)
            {
                $info['Session ID'] = Shared::escape(session_id());
                $cp = session_get_cookie_params();
                $info['Cookie Lifetime'] = $cp['lifetime'] === 0 ? 'Browser session' : $cp['lifetime'] . 's';
                $info['Cookie Path']     = Shared::escape($cp['path']);
                $info['Cookie Domain']   = Shared::escape($cp['domain'] ?: 'Current host');
                $info['Secure']          = $cp['secure']   ? 'Yes' : 'No';
                $info['HTTP Only']       = $cp['httponly'] ? 'Yes' : 'No';
                if (!empty($cp['samesite']))
                {
                    $info['SameSite'] = Shared::escape($cp['samesite']);
                }
            }

            $html = Shared::buildSection('Session Configuration', Shared::buildParametersHtml($info));

            if ($status === PHP_SESSION_ACTIVE)
            {
                $sessionData = $_SESSION ?? [];
                $label = 'Session Data (' . count($sessionData) . ' variable' . (count($sessionData) !== 1 ? 's' : '') . ')';
                $html .= Shared::buildSection($label, Shared::buildParametersHtml($sessionData));
            }

            return $html;
        }
    }
