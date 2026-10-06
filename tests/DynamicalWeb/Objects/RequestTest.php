<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;
    use PHPUnit\Framework\TestCase;

    class RequestTest extends TestCase
    {
        private function request(array $query = [], array $form = [], array $body = []): Request
        {
            return WebSessionFixture::makeRequest(['query' => $query, 'form' => $form, 'body' => $body]);
        }

        // getIntParameter

        public function testIntParameterParsesWholeNumbers(): void
        {
            $request = $this->request(['page' => '3', 'neg' => '-4', 'spaced' => ' 12 ', 'plus' => '+5', 'native' => 9]);

            $this->assertSame(3, $request->getIntParameter('page'));
            $this->assertSame(-4, $request->getIntParameter('neg'));
            $this->assertSame(12, $request->getIntParameter('spaced'));
            $this->assertSame(5, $request->getIntParameter('plus'));
            $this->assertSame(9, $request->getIntParameter('native'));
        }

        public function testIntParameterRejectsNonIntegers(): void
        {
            $request = $this->request(['float' => '1.5', 'text' => 'abc', 'mixed' => '12abc', 'empty' => '', 'array' => ['1'], 'huge' => '99999999999999999999999']);

            foreach (['float', 'text', 'mixed', 'empty', 'array', 'huge', 'missing'] as $name)
            {
                $this->assertNull($request->getIntParameter($name), $name);
                $this->assertSame(1, $request->getIntParameter($name, 1), $name);
            }
        }

        public function testIntParameterClampsToBounds(): void
        {
            $request = $this->request(['low' => '-10', 'high' => '500', 'ok' => '20']);

            $this->assertSame(1, $request->getIntParameter('low', 1, 1, 100));
            $this->assertSame(100, $request->getIntParameter('high', 1, 1, 100));
            $this->assertSame(20, $request->getIntParameter('ok', 1, 1, 100));
        }

        public function testIntParameterUsesMergedPrecedence(): void
        {
            $request = $this->request(['page' => '1'], ['page' => '3'], ['page' => '2']);

            $this->assertSame(3, $request->getIntParameter('page'));
        }

        // getBoolParameter

        public function testBoolParameter(): void
        {
            $request = $this->request(['a' => '1', 'b' => 'true', 'c' => 'on', 'd' => 'yes', 'e' => '0', 'f' => 'false', 'g' => 'off', 'h' => 'no', 'i' => 'maybe', 'j' => '', 'k' => ['x']]);

            foreach (['a', 'b', 'c', 'd'] as $name)
            {
                $this->assertTrue($request->getBoolParameter($name), $name);
            }
            foreach (['e', 'f', 'g', 'h'] as $name)
            {
                $this->assertFalse($request->getBoolParameter($name), $name);
            }
            foreach (['i', 'j', 'k', 'missing'] as $name)
            {
                $this->assertNull($request->getBoolParameter($name), $name);
                $this->assertTrue($request->getBoolParameter($name, true), $name);
            }
        }

        // getStringParameter

        public function testStringParameter(): void
        {
            $request = $this->request(['q' => '  search  ', 'n' => 5, 'arr' => ['a']]);

            $this->assertSame('  search  ', $request->getStringParameter('q'));
            $this->assertSame('search', $request->getStringParameter('q', trim: true));
            $this->assertSame('5', $request->getStringParameter('n'));
            $this->assertNull($request->getStringParameter('arr'));
            $this->assertSame('default', $request->getStringParameter('missing', 'default'));
        }
    }
