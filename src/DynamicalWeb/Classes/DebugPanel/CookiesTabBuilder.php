<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;
    use DynamicalWeb\Classes\DebugPanel as DebugPanelClass;
    use DynamicalWeb\Objects\Response;

    class CookiesTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $response = DebugPanelClass::$currentResponse;
            return self::buildAddCookieSection()
                 . self::buildRequestCookiesSection()
                 . self::buildResponseCookiesSection($response);
        }

        /**
         * Builds the request cookies section of the debug panel.
         *
         * @return string The HTML content for the request cookies section.
         */
        protected static function buildRequestCookiesSection(): string
        {
            $cookies = $_COOKIE;

            if (empty($cookies))
            {
                return self::buildSection(
                    'Request cookies (0)',
                    '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">No cookies in current request</div>'
                );
            }

            $html = '';
            foreach ($cookies as $name => $value)
            {
                $safeId    = 'dwck' . md5((string) $name);
                $jsName    = json_encode((string) $name,  JSON_HEX_QUOT | JSON_HEX_TAG);
                $attrName  = htmlspecialchars((string) $name,  ENT_QUOTES, 'UTF-8');
                $attrValue = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                $dispName  = self::escape((string) $name);
                $dispValue = self::escape((string) $value);

                // View row
                $html .= '<div class="dw-param-item dw-cookie-view-row" id="' . $safeId . '-view">'
                       . '<span class="dw-param-key">' . $dispName . '</span>'
                       . '<span class="dw-param-value" style="flex:1;">' . $dispValue . '</span>'
                       . '<span class="dw-cookie-actions">'
                       . '<button type="button" class="dw-btn dw-btn-edit" data-safeid="' . $safeId . '" data-name="' . $attrName . '">&#9998; Edit</button>'
                       . '<button type="button" class="dw-btn dw-btn-danger" data-action="del-ck" data-name="' . $attrName . '">&#215; Delete</button>'
                       . '</span>'
                       . '</div>';

                // Edit form row
                $html .= '<div class="dw-ck-edit-form" id="' . $safeId . '-edit" style="display:none;" data-safeid="' . $safeId . '">'
                       . '<div class="dw-ck-form-header">' . $dispName . '</div>'
                       . '<div class="dw-ck-form-grid">'
                       . self::field('Value',    $safeId . '-val',     'text',          $attrValue,  'dw-col-span-2')
                       . self::field('Path',     $safeId . '-path',    'text',          '/',         '')
                       . self::field('Domain',   $safeId . '-domain',  'text',          '',          '', '(current origin)')
                       . self::field('Expires',  $safeId . '-expires', 'datetime-local','',          '')
                       . '<div class="dw-ck-field">'
                       .   '<label class="dw-ck-label">Expires</label>'
                       .   '<label class="dw-ck-check"><input type="checkbox" id="' . $safeId . '-session" checked onchange="dwCkToggleExpires(this,\'' . $safeId . '-expires\')"> Session cookie</label>'
                       . '</div>'
                       . self::select('SameSite', $safeId . '-samesite', ['Lax' => 'Lax', 'Strict' => 'Strict', 'None' => 'None'], 'Lax', '')
                       . '<div class="dw-ck-field">'
                       .   '<label class="dw-ck-label">Flags</label>'
                       .   '<div style="display:flex;gap:12px;">'
                       .     '<label class="dw-ck-check"><input type="checkbox" id="' . $safeId . '-secure"> Secure</label>'
                       .     '<span class="dw-ck-note">HttpOnly is server-only</span>'
                       .   '</div>'
                       . '</div>'
                       . '</div>'
                       . '<div class="dw-ck-form-actions">'
                       . '<button type="button" class="dw-btn dw-btn-save" data-action="save-ck" data-safeid="' . $safeId . '" data-name="' . $attrName . '">&#10003; Save</button>'
                       . '<button type="button" class="dw-btn" data-action="cancel-ck" data-safeid="' . $safeId . '">Cancel</button>'
                       . '</div>'
                       . '</div>';
            }

            return self::buildSection('Request cookies (' . count($cookies) . ')', $html);
        }

        /**
         * Builds the response cookies section of the debug panel.
         *
         * @param Response $response The current response object to extract cookies from.
         * @return string The HTML content for the response cookies section.
         */
        protected static function buildResponseCookiesSection(Response $response): string
        {
            $cookies = $response->getCookies();

            if (empty($cookies))
            {
                return '';
            }

            $html = '';
            foreach ($cookies as $cookie)
            {
                $expires = $cookie->getExpires();
                $attrs   = [];
                $attrs[] = $expires === 0 ? 'session' : 'expires ' . date('Y-m-d H:i:s', $expires);
                if ($cookie->getPath() !== '/')  $attrs[] = 'path: '   . self::escape($cookie->getPath());
                if ($cookie->getDomain() !== '')  $attrs[] = 'domain: ' . self::escape($cookie->getDomain());
                if ($cookie->isSecure())          $attrs[] = 'secure';
                if ($cookie->isHttpOnly())        $attrs[] = 'httpOnly';

                $html .= '<div class="dw-param-item">'
                       . '<span class="dw-param-key">'   . self::escape($cookie->getName())  . '</span>'
                       . '<span class="dw-param-value" style="flex:1;">' . self::escape($cookie->getValue()) . '</span>'
                       . '<span style="color:#888;font-size:10px;white-space:nowrap;padding-left:8px;">[' . implode(', ', $attrs) . ']</span>'
                       . '</div>';
            }

            return self::buildSection(
                'Response cookies (' . count($cookies) . ') &mdash; <span style="font-weight:normal;color:#777;">pending, not yet sent</span>',
                $html
            );
        }

        /**
         * Builds the "Add / update cookie" section of the debug panel, which includes a form for setting new cookies or updating existing ones.
         *
         * @return string The HTML content for the add/update cookie section.
         */
        protected static function buildAddCookieSection(): string
        {
            $content = '<div class="dw-ck-edit-form" style="border:none;">'
                     . '<div class="dw-ck-form-grid">'
                     . self::field('Name',    'dw-ck-new-name',    'text',          '', '',         'cookie_name')
                     . self::field('Value',   'dw-ck-new-value',   'text',          '', 'dw-col-span-2', 'cookie_value')
                     . self::select('SameSite', 'dw-ck-new-samesite', ['Lax' => 'Lax', 'Strict' => 'Strict', 'None' => 'None'], 'Lax', '')
                     . self::field('Path',    'dw-ck-new-path',    'text',          '/', '',        '')
                     . self::field('Domain',  'dw-ck-new-domain',  'text',          '', '',         '(current origin)')
                     . '<div class="dw-ck-field">'
                     .   '<label class="dw-ck-label">Expires</label>'
                     .   '<div style="display:flex;flex-direction:column;gap:3px;">'
                     .     '<input type="datetime-local" id="dw-ck-new-expires" class="dw-input" style="width:100%;" disabled>'
                     .     '<label class="dw-ck-check"><input type="checkbox" id="dw-ck-new-session" checked onchange="dwCkToggleExpires(this,\'dw-ck-new-expires\')"> Session cookie</label>'
                     .   '</div>'
                     . '</div>'
                     . '<div class="dw-ck-field">'
                     .   '<label class="dw-ck-label">Flags</label>'
                     .   '<div style="display:flex;gap:12px;">'
                     .     '<label class="dw-ck-check"><input type="checkbox" id="dw-ck-new-secure"> Secure</label>'
                     .     '<span class="dw-ck-note">HttpOnly requires server</span>'
                     .   '</div>'
                     . '</div>'
                     . '</div>'
                     . '<div class="dw-ck-form-actions">'
                     . '<button type="button" class="dw-btn dw-btn-save" onclick="dwAddCookie()">&#43; Set cookie</button>'
                     . '</div>'
                     . '</div>';

            return self::buildSection('Add / update cookie', $content);
        }

        /**
         * Helper method to build a form field with a label and input/select element.
         *
         * @param string $label The label text for the field.
         * @param string $id The ID attribute for the input/select element.
         * @param string $type The type of the input element (e.g. 'text', 'datetime-local') or 'select' for a dropdown.
         * @param string $value The current value to populate the input/select with.
         * @param string $extraClass Additional CSS classes to apply to the field container.
         * @param string $placeholder Optional placeholder text for input fields.
         * @return string The HTML content for the form field.
         */
        protected static function field(string $label, string $id, string $type, string $value, string $extraClass, string $placeholder=''): string
        {
            $attrVal  = htmlspecialchars($value,       ENT_QUOTES, 'UTF-8');
            $attrPh   = htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8');
            $disabled = ($type === 'datetime-local' && str_contains($id, 'expires')) ? ' disabled' : '';
            return '<div class="dw-ck-field' . ($extraClass ? ' ' . $extraClass : '') . '">'
                 . '<label class="dw-ck-label" for="' . $id . '">' . self::escape($label) . '</label>'
                 . '<input type="' . $type . '" id="' . $id . '" class="dw-input" style="width:100%;"'
                 . ' value="' . $attrVal . '"'
                 . ($attrPh ? ' placeholder="' . $attrPh . '"' : '')
                 . $disabled . '>'
                 . '</div>';
        }

        /**
         * Helper method to build a select dropdown field with a label.
         *
         * @param string $label The label text for the field.
         * @param string $id The ID attribute for the select element.
         * @param array $options An associative array of option values and display texts.
         * @param string $selected The currently selected value.
         * @param string $extraClass Additional CSS classes to apply to the field container.
         * @return string The HTML content for the select field.
         */
        protected static function select(string $label, string $id, array $options, string $selected, string $extraClass): string
        {
            $opts = '';
            foreach ($options as $val => $text)
            {
                $sel   = $val === $selected ? ' selected' : '';
                $opts .= '<option value="' . self::escape($val) . '"' . $sel . '>' . self::escape($text) . '</option>';
            }
            return '<div class="dw-ck-field' . ($extraClass ? ' ' . $extraClass : '') . '">'
                 . '<label class="dw-ck-label" for="' . $id . '">' . self::escape($label) . '</label>'
                 . '<select id="' . $id . '" class="dw-input" style="width:100%;">' . $opts . '</select>'
                 . '</div>';
        }
    }
