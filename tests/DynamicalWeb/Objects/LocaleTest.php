<?php

    namespace DynamicalWeb\Objects;

    use PHPUnit\Framework\TestCase;

    class LocaleTest extends TestCase
    {
        private function createLocale(): Locale
        {
            return new Locale('en', [
                'home' => [
                    'welcome' => 'Welcome to our site!',
                    'greeting' => 'Hello, {username}!',
                    'stats' => 'You have {count} items in {location}.',
                ],
                'about' => [
                    'title' => 'About Us',
                    'description' => 'We are a team of developers.',
                ],
            ]);
        }

        public function testGetLocaleCode(): void
        {
            $locale = $this->createLocale();
            $this->assertEquals('en', $locale->getLocaleCode());
        }

        public function testGetLocaleData(): void
        {
            $locale = $this->createLocale();

            $homeData = $locale->getLocaleData('home');
            $this->assertIsArray($homeData);
            $this->assertArrayHasKey('welcome', $homeData);
            $this->assertArrayHasKey('greeting', $homeData);
        }

        public function testGetLocaleDataReturnsNullForMissing(): void
        {
            $locale = $this->createLocale();
            $this->assertNull($locale->getLocaleData('nonexistent'));
        }

        public function testGetStringSimple(): void
        {
            $locale = $this->createLocale();
            $this->assertEquals('Welcome to our site!', $locale->getString('home', 'welcome'));
        }

        public function testGetStringWithSingleReplacement(): void
        {
            $locale = $this->createLocale();
            $result = $locale->getString('home', 'greeting', ['username' => 'John']);
            $this->assertEquals('Hello, John!', $result);
        }

        public function testGetStringWithMultipleReplacements(): void
        {
            $locale = $this->createLocale();
            $result = $locale->getString('home', 'stats', ['count' => '5', 'location' => 'your cart']);
            $this->assertEquals('You have 5 items in your cart.', $result);
        }

        public function testGetStringWithNoMatchingPlaceholder(): void
        {
            $locale = $this->createLocale();
            $result = $locale->getString('home', 'greeting', ['nonexistent' => 'value']);
            $this->assertEquals('Hello, {username}!', $result);
        }

        public function testGetStringReturnsNullForMissingLocaleId(): void
        {
            $locale = $this->createLocale();
            $this->assertNull($locale->getString('nonexistent', 'key'));
        }

        public function testGetStringReturnsNullForMissingKey(): void
        {
            $locale = $this->createLocale();
            $this->assertNull($locale->getString('home', 'nonexistent_key'));
        }

        public function testHasLocaleId(): void
        {
            $locale = $this->createLocale();
            $this->assertTrue($locale->hasLocaleId('home'));
            $this->assertTrue($locale->hasLocaleId('about'));
            $this->assertFalse($locale->hasLocaleId('contact'));
        }

        public function testHasKey(): void
        {
            $locale = $this->createLocale();
            $this->assertTrue($locale->hasKey('home', 'welcome'));
            $this->assertTrue($locale->hasKey('about', 'title'));
            $this->assertFalse($locale->hasKey('home', 'nonexistent'));
            $this->assertFalse($locale->hasKey('nonexistent', 'key'));
        }

        public function testGetLocaleIds(): void
        {
            $locale = $this->createLocale();
            $ids = $locale->getLocaleIds();
            $this->assertCount(2, $ids);
            $this->assertContains('home', $ids);
            $this->assertContains('about', $ids);
        }

        public function testEmptyLocaleData(): void
        {
            $locale = new Locale('empty', []);
            $this->assertEquals('empty', $locale->getLocaleCode());
            $this->assertEmpty($locale->getLocaleIds());
            $this->assertFalse($locale->hasLocaleId('anything'));
            $this->assertNull($locale->getString('anything', 'key'));
        }

        public function testGetStringCastsReplacementToString(): void
        {
            $locale = $this->createLocale();
            $result = $locale->getString('home', 'greeting', ['username' => 42]);
            $this->assertEquals('Hello, 42!', $result);
        }
    }
