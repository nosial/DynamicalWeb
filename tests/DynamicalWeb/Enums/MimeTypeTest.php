<?php

    namespace DynamicalWeb\Enums;

    use PHPUnit\Framework\TestCase;

    class MimeTypeTest extends TestCase
    {
        // Text types

        public function testHtmlExtension(): void
        {
            $this->assertEquals(MimeType::HTML, MimeType::fromExtension('html'));
        }

        public function testHtmExtension(): void
        {
            $this->assertEquals(MimeType::HTML, MimeType::fromExtension('htm'));
        }

        public function testCssExtension(): void
        {
            $this->assertEquals(MimeType::CSS, MimeType::fromExtension('css'));
        }

        public function testJsExtension(): void
        {
            $this->assertEquals(MimeType::JAVASCRIPT, MimeType::fromExtension('js'));
        }

        public function testMjsExtension(): void
        {
            $this->assertEquals(MimeType::JAVASCRIPT, MimeType::fromExtension('mjs'));
        }

        public function testJsonExtension(): void
        {
            $this->assertEquals(MimeType::JSON, MimeType::fromExtension('json'));
        }

        public function testYamlExtension(): void
        {
            $this->assertEquals(MimeType::YAML, MimeType::fromExtension('yaml'));
        }

        public function testYmlExtension(): void
        {
            $this->assertEquals(MimeType::YAML, MimeType::fromExtension('yml'));
        }

        public function testXmlExtension(): void
        {
            $this->assertEquals(MimeType::XML, MimeType::fromExtension('xml'));
        }

        public function testTxtExtension(): void
        {
            $this->assertEquals(MimeType::TEXT, MimeType::fromExtension('txt'));
        }

        public function testCsvExtension(): void
        {
            $this->assertEquals(MimeType::CSV, MimeType::fromExtension('csv'));
        }

        // Image types

        public function testPngExtension(): void
        {
            $this->assertEquals(MimeType::PNG, MimeType::fromExtension('png'));
        }

        public function testJpgExtension(): void
        {
            $this->assertEquals(MimeType::JPEG, MimeType::fromExtension('jpg'));
        }

        public function testJpegExtension(): void
        {
            $this->assertEquals(MimeType::JPEG, MimeType::fromExtension('jpeg'));
        }

        public function testGifExtension(): void
        {
            $this->assertEquals(MimeType::GIF, MimeType::fromExtension('gif'));
        }

        public function testSvgExtension(): void
        {
            $this->assertEquals(MimeType::SVG, MimeType::fromExtension('svg'));
        }

        public function testIcoExtension(): void
        {
            $this->assertEquals(MimeType::ICO, MimeType::fromExtension('ico'));
        }

        public function testWebpExtension(): void
        {
            $this->assertEquals(MimeType::WEBP, MimeType::fromExtension('webp'));
        }

        // Case insensitivity

        public function testUppercaseExtension(): void
        {
            $this->assertEquals(MimeType::HTML, MimeType::fromExtension('HTML'));
        }

        public function testMixedCaseExtension(): void
        {
            $this->assertEquals(MimeType::JSON, MimeType::fromExtension('Json'));
        }

        // Font types

        public function testWoffExtension(): void
        {
            $this->assertEquals(MimeType::WOFF, MimeType::fromExtension('woff'));
        }

        public function testWoff2Extension(): void
        {
            $this->assertEquals(MimeType::WOFF2, MimeType::fromExtension('woff2'));
        }

        public function testTtfExtension(): void
        {
            $this->assertEquals(MimeType::TTF, MimeType::fromExtension('ttf'));
        }

        // Archive types

        public function testZipExtension(): void
        {
            $this->assertEquals(MimeType::ZIP, MimeType::fromExtension('zip'));
        }

        public function testPdfExtension(): void
        {
            $this->assertEquals(MimeType::PDF, MimeType::fromExtension('pdf'));
        }

        // Default for unknown extension

        public function testUnknownExtensionReturnsOctetStream(): void
        {
            $this->assertEquals(MimeType::OCTET_STREAM, MimeType::fromExtension('xyz_unknown'));
        }

        public function testEmptyExtensionReturnsOctetStream(): void
        {
            $this->assertEquals(MimeType::OCTET_STREAM, MimeType::fromExtension(''));
        }
    }
