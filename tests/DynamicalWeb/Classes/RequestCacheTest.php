<?php

    namespace DynamicalWeb\Classes;

    use PHPUnit\Framework\TestCase;
    use RuntimeException;

    class RequestCacheTest extends TestCase
    {
        protected function setUp(): void
        {
            RequestCache::clear();
        }

        public function testStoreFetchExistsDelete(): void
        {
            $success = true;
            $this->assertNull(RequestCache::fetch('key', $success));
            $this->assertFalse($success);

            RequestCache::store('key', ['value']);
            $this->assertTrue(RequestCache::exists('key'));
            $this->assertSame(['value'], RequestCache::fetch('key', $success));
            $this->assertTrue($success);

            $this->assertTrue(RequestCache::delete('key'));
            $this->assertFalse(RequestCache::exists('key'));
            $this->assertFalse(RequestCache::delete('key'));
        }

        public function testNullIsCachedAsAHit(): void
        {
            RequestCache::store('nothing', null);

            $success = false;
            $this->assertNull(RequestCache::fetch('nothing', $success));
            $this->assertTrue($success);
        }

        public function testRememberInvokesCallbackOnce(): void
        {
            $calls = 0;
            $callback = function () use (&$calls) { $calls++; return null; };

            $this->assertNull(RequestCache::remember('lookup', $callback));
            $this->assertNull(RequestCache::remember('lookup', $callback));
            $this->assertSame(1, $calls);
        }

        public function testRememberDoesNotCacheExceptions(): void
        {
            $calls = 0;
            try
            {
                RequestCache::remember('failing', function () use (&$calls) { $calls++; throw new RuntimeException('down'); });
            }
            catch (RuntimeException)
            {
            }

            $this->assertFalse(RequestCache::exists('failing'));
            $this->assertSame('ok', RequestCache::remember('failing', fn() => 'ok'));
        }

        public function testClearAndCount(): void
        {
            RequestCache::store('a', 1);
            RequestCache::store('b', 2);
            $this->assertSame(2, RequestCache::count());

            RequestCache::clear();
            $this->assertSame(0, RequestCache::count());
        }
    }
