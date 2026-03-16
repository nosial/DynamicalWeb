<?php

    namespace DynamicalWeb\Objects;

    use PHPUnit\Framework\TestCase;

    class CookieTest extends TestCase
    {
        public function testConstructorDefaults(): void
        {
            $cookie = new Cookie('session', 'abc123');

            $this->assertEquals('session', $cookie->getName());
            $this->assertEquals('abc123', $cookie->getValue());
            $this->assertEquals(0, $cookie->getExpires());
            $this->assertEquals('/', $cookie->getPath());
            $this->assertEquals('', $cookie->getDomain());
            $this->assertFalse($cookie->isSecure());
            $this->assertFalse($cookie->isHttpOnly());
            $this->assertEquals('Lax', $cookie->getSameSite());
        }

        public function testConstructorFullParameters(): void
        {
            $cookie = new Cookie('token', 'xyz789', 3600, '/api', 'example.com', true, true, 'Strict');

            $this->assertEquals('token', $cookie->getName());
            $this->assertEquals('xyz789', $cookie->getValue());
            $this->assertEquals(3600, $cookie->getExpires());
            $this->assertEquals('/api', $cookie->getPath());
            $this->assertEquals('example.com', $cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertTrue($cookie->isHttpOnly());
            $this->assertEquals('Strict', $cookie->getSameSite());
        }

        public function testSetters(): void
        {
            $cookie = new Cookie('test', 'value');

            $cookie->setName('updated');
            $this->assertEquals('updated', $cookie->getName());

            $cookie->setValue('new_value');
            $this->assertEquals('new_value', $cookie->getValue());

            $cookie->setExpires(7200);
            $this->assertEquals(7200, $cookie->getExpires());

            $cookie->setPath('/admin');
            $this->assertEquals('/admin', $cookie->getPath());

            $cookie->setDomain('sub.example.com');
            $this->assertEquals('sub.example.com', $cookie->getDomain());

            $cookie->setSecure(true);
            $this->assertTrue($cookie->isSecure());

            $cookie->setHttpOnly(true);
            $this->assertTrue($cookie->isHttpOnly());

            $cookie->setSameSite('None');
            $this->assertEquals('None', $cookie->getSameSite());
        }

        public function testToArray(): void
        {
            $cookie = new Cookie('auth', 'token123', 1800, '/app', 'mysite.com', true, true, 'Strict');
            $array = $cookie->toArray();

            $this->assertEquals('auth', $array['name']);
            $this->assertEquals('token123', $array['value']);
            $this->assertEquals(1800, $array['expires']);
            $this->assertEquals('/app', $array['path']);
            $this->assertEquals('mysite.com', $array['domain']);
            $this->assertTrue($array['secure']);
            $this->assertTrue($array['httpOnly']);
            $this->assertEquals('Strict', $array['sameSite']);
        }

        public function testFromArray(): void
        {
            $data = [
                'name' => 'pref',
                'value' => 'dark_mode',
                'expires' => 86400,
                'path' => '/',
                'domain' => 'example.com',
                'secure' => true,
                'httpOnly' => false,
                'sameSite' => 'None',
            ];

            $cookie = Cookie::fromArray($data);

            $this->assertInstanceOf(Cookie::class, $cookie);
            $this->assertEquals('pref', $cookie->getName());
            $this->assertEquals('dark_mode', $cookie->getValue());
            $this->assertEquals(86400, $cookie->getExpires());
            $this->assertEquals('/', $cookie->getPath());
            $this->assertEquals('example.com', $cookie->getDomain());
            $this->assertTrue($cookie->isSecure());
            $this->assertFalse($cookie->isHttpOnly());
            $this->assertEquals('None', $cookie->getSameSite());
        }

        public function testFromArrayDefaults(): void
        {
            $data = [
                'name' => 'minimal',
                'value' => 'data',
            ];

            $cookie = Cookie::fromArray($data);

            $this->assertEquals(0, $cookie->getExpires());
            $this->assertEquals('/', $cookie->getPath());
            $this->assertEquals('', $cookie->getDomain());
            $this->assertFalse($cookie->isSecure());
            $this->assertFalse($cookie->isHttpOnly());
            $this->assertEquals('Lax', $cookie->getSameSite());
        }

        public function testToArrayFromArrayRoundTrip(): void
        {
            $original = new Cookie('session', 'abc', 3600, '/api', 'example.com', true, true, 'Strict');
            $restored = Cookie::fromArray($original->toArray());

            $this->assertEquals($original->getName(), $restored->getName());
            $this->assertEquals($original->getValue(), $restored->getValue());
            $this->assertEquals($original->getExpires(), $restored->getExpires());
            $this->assertEquals($original->getPath(), $restored->getPath());
            $this->assertEquals($original->getDomain(), $restored->getDomain());
            $this->assertEquals($original->isSecure(), $restored->isSecure());
            $this->assertEquals($original->isHttpOnly(), $restored->isHttpOnly());
            $this->assertEquals($original->getSameSite(), $restored->getSameSite());
        }
    }
