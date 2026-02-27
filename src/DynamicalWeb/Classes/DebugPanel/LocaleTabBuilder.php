<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Objects\Request;
    use DynamicalWeb\WebSession;

    class LocaleTabBuilder
    {
        public static function build(): string
        {
            try
            {
                $locale = WebSession::getLocale();
                if ($locale === null)
                {
                    return '';
                }

                $localeIds    = $locale->getLocaleIds();
                $totalStrings = 0;
                $idRows       = [];
                foreach ($localeIds as $id)
                {
                    $data  = $locale->getLocaleData($id);
                    $count = $data ? count($data) : 0;
                    $totalStrings += $count;
                    $idRows[Shared::escape($id)] = $count . ' string' . ($count !== 1 ? 's' : '');
                }

                return
                    Shared::buildSection('Locale Info', Shared::buildParametersHtml([
                        'Locale Code'    => Shared::escape($locale->getLocaleCode()),
                        'Locale IDs'     => (string) count($localeIds),
                        'Total Strings'  => (string) $totalStrings,
                    ])) .
                    (!empty($idRows) ? Shared::buildSection('Locale IDs (' . count($idRows) . ')', Shared::buildParametersHtml($idRows)) : '');
            }
            catch (\Throwable)
            {
                return '';
            }
        }

        public static function buildLocaleSwitcherHtml(?Request $request): string
        {
            try
            {
                $instance = WebSession::getInstance();
                if ($instance === null)
                {
                    return '';
                }

                $availableLocales = $instance->getAvailableLocaleCodes();
                if (empty($availableLocales))
                {
                    return '';
                }

                $currentLocale = WebSession::getLocale()?->getLocaleCode() ?? '';

                $returnPath = $request ? rawurlencode($request->getPath()) : rawurlencode('/');

                $basePath = '';
                if ($request !== null)
                {
                    $bp = $instance->getWebConfiguration()->getRouter()->getBasePath();
                    $basePath = rtrim($bp, '/');
                }

                $html = '<div style="'
                      . 'background:linear-gradient(to bottom,#d4dce5 0%,#c4cdd7 100%);'
                      . 'border-bottom:2px solid #9badbd;'
                      . 'padding:5px 8px;'
                      . 'display:flex;align-items:center;gap:0;'
                      . '">'
                      . '<span style="font-family:Verdana,Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;color:#2c4a6b;margin-right:8px;white-space:nowrap;">LOCALE:</span>'
                      . '<div style="display:flex;flex-wrap:wrap;gap:3px;">';

                foreach ($availableLocales as $code)
                {
                    $isCurrent = $code === $currentLocale;
                    $url       = Shared::escape($basePath . '/dynaweb/language/' . rawurlencode($code) . '?r=' . $returnPath);
                    $label     = Shared::escape(strtoupper($code));

                    if ($isCurrent)
                    {
                        $style = 'display:inline-block;padding:2px 8px;'
                               . 'font-family:Verdana,Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;'
                               . 'background:linear-gradient(to bottom,#3d6b9e,#2c4a6b);'
                               . 'color:#fff;'
                               . 'border:1px solid #2c3e55;'
                               . 'text-decoration:none;cursor:default;white-space:nowrap;';
                    }
                    else
                    {
                        $style = 'display:inline-block;padding:2px 8px;'
                               . 'font-family:Verdana,Arial,Helvetica,sans-serif;font-size:10px;font-weight:bold;'
                               . 'background:linear-gradient(to bottom,#eef2f6,#dce4ec);'
                               . 'color:#2c4a6b;'
                               . 'border:1px solid #9badbd;'
                               . 'text-decoration:none;cursor:pointer;white-space:nowrap;';
                    }

                    $html .= '<a href="' . $url . '" target="_top" style="' . $style . '"'
                           . ($isCurrent ? ' aria-current="true"' : '')
                           . '>' . $label . '</a>';
                }

                $html .= '</div></div>';
                return $html;
            }
            catch (\Throwable)
            {
                return '';
            }
        }
    }
