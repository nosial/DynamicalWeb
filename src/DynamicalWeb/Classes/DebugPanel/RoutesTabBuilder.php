<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Objects\WebConfiguration\Route;
    use DynamicalWeb\WebSession;

    class RoutesTabBuilder
    {
        public static function build(?Route $currentRoute): string
        {
            try
            {
                $instance = WebSession::getInstance();
                if ($instance === null)
                {
                    return '';
                }

                $routes      = $instance->getWebConfiguration()->getRouter()->getRoutes();
                $webRootPath = rtrim($instance->getWebRootPath(), '/');

                if (empty($routes))
                {
                    return '';
                }

                $html = '<div class="dw-route-list">';
                $html .= '<div class="dw-route-list-header">'
                       . '<span class="dw-route-col-path">Path</span>'
                       . '<span class="dw-route-col-methods">Methods</span>'
                       . '<span class="dw-route-col-module">Module</span>'
                       . '</div>';

                foreach ($routes as $r)
                {
                    $methods    = array_map(static fn($m) => is_string($m) ? $m : $m->value, $r->getAllowedMethods());
                    $isCurrent  = $currentRoute && $currentRoute->getPath() === $r->getPath();
                    $rowClass   = $isCurrent ? 'dw-route-row dw-route-row-active' : 'dw-route-row';

                    $methodBadges = '';
                    foreach ($methods as $m)
                    {
                        $cssClass = strtolower($m) === '*' ? 'any' : strtolower($m);
                        $methodBadges .= '<span class="dw-route-method dw-route-method-' . $cssClass . '">' . Shared::escape($m) . '</span>';
                    }

                    $locale     = $r->getLocaleId() ? ' <span class="dw-route-locale">' . Shared::escape($r->getLocaleId()) . '</span>' : '';
                    $fullModule = $webRootPath . '/' . ltrim($r->getModule(), '/');

                    $html .= '<div class="' . $rowClass . '">'
                           . '<span class="dw-route-col-path">' . Shared::escape($r->getPath()) . $locale . '</span>'
                           . '<span class="dw-route-col-methods">' . $methodBadges . '</span>'
                           . '<span class="dw-route-col-module">' . Shared::escape($fullModule) . '</span>'
                           . '</div>';
                }

                $html .= '</div>';
                return $html;
            }
            catch (\Throwable)
            {
                return '';
            }
        }
    }
