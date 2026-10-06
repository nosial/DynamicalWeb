<?php

    namespace DynamicalWeb\Objects;

    use PHPUnit\Framework\TestCase;

    class CookieSessionTest extends TestCase
    {
        public function testCookieOptionsRoundTrip(): void
        {
            $options = ['path' => '/app', 'domain' => 'example.com', 'secure' => true, 'http_only' => true, 'same_site' => 'Strict'];
            $session = new CookieSession('abc', ['user' => 1], 100, 'fp', $options);

            $restored = CookieSession::fromArray($session->toArray());
            $this->assertSame($options, $restored->getCookieOptions());
            $this->assertSame(['user' => 1], $restored->getData());
            $this->assertSame('fp', $restored->getFingerprint());
        }

        public function testSessionsStoredBeforeCookieOptionsStillLoad(): void
        {
            $session = CookieSession::fromArray(['session_id' => 'abc', 'data' => ['user' => 1], 'expires' => 100, 'fingerprint' => 'fp']);

            $this->assertSame([], $session->getCookieOptions());
            $this->assertSame(1, $session->get('user'));
        }

        public function testSetFingerprint(): void
        {
            $session = new CookieSession('abc');
            $session->setFingerprint('new');

            $this->assertSame('new', $session->getFingerprint());
            $this->assertSame('new', $session->toArray()['fingerprint']);
        }
    }
