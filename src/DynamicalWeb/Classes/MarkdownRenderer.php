<?php

    namespace DynamicalWeb\Classes;

    use FastVolt\Helper\Markdown;
    use RuntimeException;
    use Throwable;

    /**
     * Converts Markdown into HTML for templates, see {@see \DynamicalWeb\Html\Functions::printMarkdown()}.
     *
     * Sources are read like file_get_contents() reads them: a local path, or any URL a registered stream wrapper
     * supports (http://, https://, ftp://, file://, data://, ...). Rendered local files are cached in APCu until the
     * file's modification time or size changes, every source is cached in the {@see RequestCache} for the request.
     */
    class MarkdownRenderer
    {
        /**
         * Seconds to wait for a remote source before giving up
         */
        public const REMOTE_TIMEOUT = 10;

        /**
         * Converts a Markdown string into HTML.
         *
         * @param string $markdown The Markdown source
         * @param bool $sanitize If True, raw HTML in the Markdown is escaped and unsafe link schemes (such as
         *                       javascript:) are removed. Only disable this for Markdown from a trusted source.
         * @return string The rendered HTML
         * @throws RuntimeException Thrown if the Markdown could not be converted
         */
        public static function render(string $markdown, bool $sanitize=true): string
        {
            if (trim($markdown) === '')
            {
                return '';
            }

            try
            {
                return (string)Markdown::new($sanitize)->setContent($markdown)->getHtml();
            }
            catch (Throwable $e)
            {
                throw new RuntimeException(sprintf('Failed to render Markdown: %s', $e->getMessage()), 0, $e);
            }
        }

        /**
         * Reads a Markdown source and converts it into HTML.
         *
         * @param string $source A local file path or a URL supported by a registered stream wrapper
         * @param bool $sanitize If True, raw HTML is escaped and unsafe link schemes are removed, see {@see self::render()}
         * @return string The rendered HTML
         * @throws RuntimeException Thrown if the source could not be read or converted
         */
        public static function renderSource(string $source, bool $sanitize=true): string
        {
            $cacheKey = 'dw_markdown_' . md5($source . '|' . (int)$sanitize);
            return RequestCache::remember($cacheKey, function() use ($source, $sanitize, $cacheKey): string
            {
                $startTime = microtime(true);

                try
                {
                    return self::isUrl($source)
                        ? self::render(self::readSource($source), $sanitize)
                        : self::renderLocalFile($source, $sanitize, $cacheKey);
                }
                finally
                {
                    DebugPanel::trackFileExecution($source, 'markdown', microtime(true) - $startTime);
                }
            });
        }

        /**
         * Returns True if the source is a URL handled by a stream wrapper rather than a local path.
         *
         * @param string $source The Markdown source
         * @return bool True if the source is a URL
         */
        public static function isUrl(string $source): bool
        {
            return str_contains($source, '://') || str_starts_with($source, 'data:');
        }

        /**
         * Renders a local Markdown file, reusing the APCu copy while the file is unchanged.
         *
         * @param string $path The path to the Markdown file
         * @param bool $sanitize Whether to sanitize the output
         * @param string $cacheKey The APCu key for this file and sanitize option
         * @return string The rendered HTML
         * @throws RuntimeException Thrown if the file could not be read or converted
         */
        private static function renderLocalFile(string $path, bool $sanitize, string $cacheKey): string
        {
            if (!is_file($path))
            {
                throw new RuntimeException(sprintf('Markdown file not found at "%s"', $path));
            }

            $mtime = filemtime($path);
            $size = filesize($path);
            $cached = Apcu::fetch($cacheKey, $hit);
            if ($hit && is_array($cached) && $cached['mtime'] === $mtime && $cached['size'] === $size)
            {
                return $cached['html'];
            }

            $html = self::render(self::readSource($path), $sanitize);
            Apcu::store($cacheKey, ['mtime' => $mtime, 'size' => $size, 'html' => $html]);
            return $html;
        }

        /**
         * Reads the raw contents of a source with file_get_contents(), with a timeout for remote sources.
         *
         * @param string $source A local file path or a URL
         * @return string The contents of the source
         * @throws RuntimeException Thrown if the source could not be read
         */
        private static function readSource(string $source): string
        {
            $context = stream_context_create([
                'http' => [
                    'timeout' => self::REMOTE_TIMEOUT,
                    'follow_location' => 1,
                    'user_agent' => 'DynamicalWeb',
                    'header' => "Accept: text/markdown, text/plain;q=0.9, */*;q=0.8\r\n",
                ],
                'ftp' => ['timeout' => self::REMOTE_TIMEOUT],
            ]);

            $error = null;
            set_error_handler(function(int $code, string $message) use (&$error): bool
            {
                $error = $message;
                return true;
            });

            try
            {
                $contents = file_get_contents($source, false, $context);
            }
            finally
            {
                restore_error_handler();
            }

            if ($contents === false)
            {
                throw new RuntimeException(sprintf('Failed to read Markdown source "%s"%s', $source, $error !== null ? ': ' . $error : ''));
            }

            return $contents;
        }
    }
