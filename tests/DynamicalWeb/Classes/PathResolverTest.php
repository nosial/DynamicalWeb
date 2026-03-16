<?php

    namespace DynamicalWeb\Classes;

    use PHPUnit\Framework\TestCase;

    class PathResolverTest extends TestCase
    {
        // sanitizePath tests

        public function testSanitizePathRemovesSimpleTraversal(): void
        {
            $this->assertEquals('etc/passwd', PathResolver::sanitizePath('../etc/passwd'));
        }

        public function testSanitizePathRemovesMultipleTraversals(): void
        {
            $this->assertEquals('etc/passwd', PathResolver::sanitizePath('../../../etc/passwd'));
        }

        public function testSanitizePathRemovesBackslashTraversal(): void
        {
            $this->assertEquals('etc\\passwd', PathResolver::sanitizePath('..\\etc\\passwd'));
        }

        public function testSanitizePathRemovesMixedTraversal(): void
        {
            $this->assertEquals('etc/passwd', PathResolver::sanitizePath('..\\../etc/passwd'));
        }

        public function testSanitizePathPreventsDoubleEncodingBypass(): void
        {
            // ....// after one pass becomes ../ — the loop must catch this
            $this->assertEquals('etc/passwd', PathResolver::sanitizePath('....//etc/passwd'));
        }

        public function testSanitizePathPreventsDeepBypass(): void
        {
            // ....../// after first pass becomes ....// then ../ then empty
            $this->assertEquals('etc/passwd', PathResolver::sanitizePath('......///etc/passwd'));
        }

        public function testSanitizePathPreventsBackslashBypass(): void
        {
            $this->assertEquals('etc\\passwd', PathResolver::sanitizePath('....\\\\etc\\passwd'));
        }

        public function testSanitizePathLeavesCleanPathUntouched(): void
        {
            $this->assertEquals('public/css/style.css', PathResolver::sanitizePath('public/css/style.css'));
        }

        public function testSanitizePathEmptyString(): void
        {
            $this->assertEquals('', PathResolver::sanitizePath(''));
        }

        public function testSanitizePathOnlyTraversal(): void
        {
            $this->assertEquals('', PathResolver::sanitizePath('../'));
        }

        public function testSanitizePathMidPathTraversal(): void
        {
            $this->assertEquals('public/style.css', PathResolver::sanitizePath('public/../style.css'));
        }

        // normalizePath tests

        public function testNormalizePathConvertsForwardSlashes(): void
        {
            $expected = str_replace('/', DIRECTORY_SEPARATOR, 'path/to/file');
            $this->assertEquals($expected, PathResolver::normalizePath('path/to/file'));
        }

        public function testNormalizePathConvertsBackslashes(): void
        {
            $expected = str_replace('\\', DIRECTORY_SEPARATOR, 'path\\to\\file');
            $this->assertEquals($expected, PathResolver::normalizePath('path\\to\\file'));
        }

        public function testNormalizePathConvertsMixed(): void
        {
            $expected = implode(DIRECTORY_SEPARATOR, ['path', 'to', 'file']);
            $this->assertEquals($expected, PathResolver::normalizePath('path/to\\file'));
        }

        public function testNormalizePathEmptyString(): void
        {
            $this->assertEquals('', PathResolver::normalizePath(''));
        }

        // buildNccPath tests

        public function testBuildNccPath(): void
        {
            $this->assertEquals(
                'ncc://com.example.app/public/index.phtml',
                PathResolver::buildNccPath('com.example.app', 'public', 'index.phtml')
            );
        }

        public function testBuildNccPathMultipleSegments(): void
        {
            $this->assertEquals(
                'ncc://net.nosial.dynamicalweb/BuiltinPages/500.phtml',
                PathResolver::buildNccPath('net.nosial.dynamicalweb', 'BuiltinPages', '500.phtml')
            );
        }

        // isValidFile tests

        public function testIsValidFileReturnsFalseForNonexistent(): void
        {
            $this->assertFalse(PathResolver::isValidFile('/nonexistent/path/to/file.txt'));
        }

        public function testIsValidFileReturnsFalseForDirectory(): void
        {
            $this->assertFalse(PathResolver::isValidFile(__DIR__));
        }

        public function testIsValidFileReturnsTrueForExistingFile(): void
        {
            $this->assertTrue(PathResolver::isValidFile(__FILE__));
        }
    }
