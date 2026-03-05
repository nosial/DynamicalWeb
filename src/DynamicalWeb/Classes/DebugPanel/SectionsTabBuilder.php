<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\Abstract\AbstractTabBuilder;
    use DynamicalWeb\Classes\DebugPanel as DebugPanelClass;
    use DynamicalWeb\WebSession;
    use Throwable;

    class SectionsTabBuilder extends AbstractTabBuilder
    {
        /**
         * @inheritDoc
         */
        public static function build(): string
        {
            $executedSections = DebugPanelClass::$executedSections;
            try
            {
                $instance = WebSession::getInstance();
                if ($instance === null)
                {
                    return '';
                }

                $sections = $instance->getSections();
                if (empty($sections))
                {
                    return '';
                }

                $configuredData = [];
                foreach ($sections as $name => $section)
                {
                    $localeId = $section->getLocaleId() ?? '—';
                    $configuredData[self::escape($name)] = self::escape($section->getModule())
                        . '<span style="color:#888;margin-left:8px;font-size:10px;">locale: ' . self::escape($localeId) . '</span>';
                }

                $html = self::buildSection('Configured Sections (' . count($sections) . ')', self::buildParametersHtml($configuredData));

                if (!empty($executedSections))
                {
                    $executedData = [];
                    foreach ($executedSections as $name => $info)
                    {
                        $count    = $info['count'];
                        $total    = self::formatTime($info['totalDuration']);
                        $avg      = $count > 0 ? self::formatTime($info['totalDuration'] / $count) : '0ms';
                        $executedData[self::escape($name)] =
                            $count . 'x &nbsp; total: ' . $total . ' &nbsp; avg: ' . $avg;
                    }
                    $html .= self::buildSection('Executed This Request (' . count($executedSections) . ')', self::buildParametersHtml($executedData));
                }
                else
                {
                    $html .= self::buildSection('Executed This Request', '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">No sections were rendered during this request</div>');
                }

                return $html;
            }
            catch (Throwable)
            {
                return '';
            }
        }
    }
