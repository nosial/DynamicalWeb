<?php

    namespace DynamicalWeb\Enums;

    use PHPUnit\Framework\TestCase;

    class ResponseCodeTest extends TestCase
    {
        public function testTryFromValidCodes(): void
        {
            $this->assertEquals(ResponseCode::OK, ResponseCode::tryFrom(200));
            $this->assertEquals(ResponseCode::CREATED, ResponseCode::tryFrom(201));
            $this->assertEquals(ResponseCode::NOT_FOUND, ResponseCode::tryFrom(404));
            $this->assertEquals(ResponseCode::INTERNAL_SERVER_ERROR, ResponseCode::tryFrom(500));
            $this->assertEquals(ResponseCode::FOUND, ResponseCode::tryFrom(302));
            $this->assertEquals(ResponseCode::MOVED_PERMANENTLY, ResponseCode::tryFrom(301));
        }

        public function testTryFromInvalidCode(): void
        {
            $this->assertNull(ResponseCode::tryFrom(999));
        }

        public function testFromValidCode(): void
        {
            $code = ResponseCode::from(200);
            $this->assertEquals(200, $code->value);
        }

        public function testEnumValues(): void
        {
            $this->assertEquals(100, ResponseCode::CONTINUE->value);
            $this->assertEquals(200, ResponseCode::OK->value);
            $this->assertEquals(301, ResponseCode::MOVED_PERMANENTLY->value);
            $this->assertEquals(302, ResponseCode::FOUND->value);
            $this->assertEquals(400, ResponseCode::BAD_REQUEST->value);
            $this->assertEquals(401, ResponseCode::UNAUTHORIZED->value);
            $this->assertEquals(403, ResponseCode::FORBIDDEN->value);
            $this->assertEquals(404, ResponseCode::NOT_FOUND->value);
            $this->assertEquals(405, ResponseCode::METHOD_NOT_ALLOWED->value);
            $this->assertEquals(500, ResponseCode::INTERNAL_SERVER_ERROR->value);
            $this->assertEquals(502, ResponseCode::BAD_GATEWAY->value);
            $this->assertEquals(503, ResponseCode::SERVICE_UNAVAILABLE->value);
        }

        public function testToString(): void
        {
            $this->assertIsString(ResponseCode::OK->toString());
            $this->assertNotEmpty(ResponseCode::OK->toString());
        }
    }
