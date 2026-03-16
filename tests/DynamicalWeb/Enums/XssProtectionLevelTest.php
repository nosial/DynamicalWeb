<?php

    namespace DynamicalWeb\Enums;

    use PHPUnit\Framework\TestCase;

    class XssProtectionLevelTest extends TestCase
    {
        public function testDisabledReturnsEmptyHeaders(): void
        {
            $headers = XssProtectionLevel::DISABLED->getHeaders();
            $this->assertEmpty($headers);
        }

        public function testLowReturnsXssProtectionHeader(): void
        {
            $headers = XssProtectionLevel::LOW->getHeaders();
            $this->assertArrayHasKey('X-XSS-Protection', $headers);
            $this->assertEquals('1; mode=block', $headers['X-XSS-Protection']);
        }

        public function testMediumReturnsCspHeader(): void
        {
            $headers = XssProtectionLevel::MEDIUM->getHeaders();
            $this->assertArrayHasKey('Content-Security-Policy', $headers);
            $this->assertStringContainsString("default-src 'self'", $headers['Content-Security-Policy']);
        }

        public function testHighReturnsCspWithNonce(): void
        {
            $nonce = 'abc123def456';
            $headers = XssProtectionLevel::HIGH->getHeaders($nonce);
            $this->assertArrayHasKey('Content-Security-Policy', $headers);
            $this->assertStringContainsString("'nonce-abc123def456'", $headers['Content-Security-Policy']);
        }

        public function testHighWithNullNonce(): void
        {
            $headers = XssProtectionLevel::HIGH->getHeaders(null);
            $this->assertArrayHasKey('Content-Security-Policy', $headers);
            $this->assertStringContainsString("'nonce-'", $headers['Content-Security-Policy']);
        }

        // toString tests

        public function testToStringDisabled(): void
        {
            $this->assertEquals('Disabled', XssProtectionLevel::DISABLED->toString());
        }

        public function testToStringLow(): void
        {
            $this->assertEquals('Low', XssProtectionLevel::LOW->toString());
        }

        public function testToStringMedium(): void
        {
            $this->assertEquals('Medium', XssProtectionLevel::MEDIUM->toString());
        }

        public function testToStringHigh(): void
        {
            $this->assertEquals('High', XssProtectionLevel::HIGH->toString());
        }

        // Backed enum values

        public function testEnumValues(): void
        {
            $this->assertEquals(0, XssProtectionLevel::DISABLED->value);
            $this->assertEquals(1, XssProtectionLevel::LOW->value);
            $this->assertEquals(2, XssProtectionLevel::MEDIUM->value);
            $this->assertEquals(3, XssProtectionLevel::HIGH->value);
        }

        public function testTryFromValidValue(): void
        {
            $this->assertEquals(XssProtectionLevel::MEDIUM, XssProtectionLevel::tryFrom(2));
        }

        public function testTryFromInvalidValue(): void
        {
            $this->assertNull(XssProtectionLevel::tryFrom(99));
        }
    }
