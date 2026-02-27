<?php

    namespace DynamicalWeb\Classes\DebugPanel;

    use DynamicalWeb\WebSession;

    class SectionsTabBuilder
    {
        public static function build(array $executedSections): string
        {
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
                    $configuredData[Shared::escape($name)] = Shared::escape($section->getModule())
                        . '<span style="color:#888;margin-left:8px;font-size:10px;">locale: ' . Shared::escape($localeId) . '</span>';
                }

                $html = Shared::buildSection('Configured Sections (' . count($sections) . ')', Shared::buildParametersHtml($configuredData));

                if (!empty($executedSections))
                {
                    $executedData = [];
                    foreach ($executedSections as $name => $info)
                    {
                        $count    = $info['count'];
                        $total    = Shared::formatTime($info['totalDuration']);
                        $avg      = $count > 0 ? Shared::formatTime($info['totalDuration'] / $count) : '0ms';
                        $executedData[Shared::escape($name)] =
                            $count . 'x &nbsp; total: ' . $total . ' &nbsp; avg: ' . $avg;
                    }
                    $html .= Shared::buildSection('Executed This Request (' . count($executedSections) . ')', Shared::buildParametersHtml($executedData));
                }
                else
                {
                    $html .= Shared::buildSection('Executed This Request', '<div style="padding:8px;font-style:italic;color:#999;text-align:center;">No sections were rendered during this request</div>');
                }

                return $html;
            }
            catch (\Throwable)
            {
                return '';
            }
        }
    }
