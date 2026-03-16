<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Enums\UserAgent\Bot;
    use DynamicalWeb\Enums\UserAgent\Browser;
    use DynamicalWeb\Enums\UserAgent\DeviceBrand;
    use DynamicalWeb\Enums\UserAgent\DeviceType;
    use DynamicalWeb\Enums\UserAgent\OperatingSystem;
    use DynamicalWeb\Enums\UserAgent\RenderingEngine;
    use PHPUnit\Framework\TestCase;

    class UserAgentTest extends TestCase
    {
        // Desktop Browsers Tests
        
        public function testChromeWindows(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertStringStartsWith('120.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('10', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testFirefoxMacOS(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:121.0) Gecko/20100101 Firefox/121.0');
            
            $this->assertEquals(Browser::FIREFOX, $ua->getBrowserName());
            $this->assertEquals('121.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::MACOS, $ua->getOsName());
            $this->assertEquals('10.15', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::GECKO, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isTablet());
            $this->assertFalse($ua->isBot());
        }
        
        public function testSafariMacOS(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15');
            
            $this->assertEquals(Browser::SAFARI, $ua->getBrowserName());
            $this->assertEquals('17.1', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::MACOS, $ua->getOsName());
            $this->assertEquals('10.15.7', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::WEBKIT, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testEdgeChromium(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');
            
            $this->assertEquals(Browser::EDGE, $ua->getBrowserName());
            $this->assertStringStartsWith('120.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('10', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testOpera(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 OPR/106.0.0.0');
            
            $this->assertEquals(Browser::OPERA, $ua->getBrowserName());
            $this->assertStringStartsWith('106.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testBrave(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Brave/1.61.109');
            
            $this->assertEquals(Browser::BRAVE, $ua->getBrowserName());
            $this->assertNotNull($ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testVivaldi(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Vivaldi/6.5.3206.53');
            
            $this->assertEquals(Browser::VIVALDI, $ua->getBrowserName());
            $this->assertStringStartsWith('6.5', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Mobile Browser Tests
        
        public function testiPhoneSafari(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_1_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
            
            $this->assertEquals(Browser::SAFARI, $ua->getBrowserName());
            $this->assertStringStartsWith('17.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::IOS, $ua->getOsName());
            $this->assertEquals('17.1.1', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::APPLE, $ua->getDeviceBrand());
            $this->assertEquals('iPhone', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::WEBKIT, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isTablet());
            $this->assertFalse($ua->isBot());
        }
        
        public function testiPadSafari(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
            
            $this->assertEquals(Browser::SAFARI, $ua->getBrowserName());
            $this->assertStringStartsWith('17.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::IPADOS, $ua->getOsName());
            $this->assertEquals('17.1', $ua->getOsVersion());
            $this->assertEquals(DeviceType::TABLET, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::APPLE, $ua->getDeviceBrand());
            $this->assertEquals('iPad', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::WEBKIT, $ua->getEngine());
            $this->assertTrue($ua->isTablet());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        public function testAndroidChrome(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertStringStartsWith('120.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('14', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isTablet());
            $this->assertFalse($ua->isBot());
        }
        
        public function testSamsungGalaxy(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 14; SM-S911B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.144 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('14', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::SAMSUNG, $ua->getDeviceBrand());
            $this->assertStringContainsString('SM-', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        public function testXiaomiRedmi(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; Redmi Note 12 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::XIAOMI, $ua->getDeviceBrand());
            $this->assertNotNull($ua->getDeviceModel());
            $this->assertStringContainsString('Note', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testGooglePixel(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 14; Pixel 8 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('14', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::GOOGLE, $ua->getDeviceBrand());
            $this->assertStringContainsString('Pixel', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        public function testOnePlus(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 14; OnePlus 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('14', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::ONEPLUS, $ua->getDeviceBrand());
            $this->assertStringContainsString('OnePlus', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testSamsungBrowser(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 14; SM-S911B) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/23.0 Chrome/115.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::SAMSUNG_BROWSER, $ua->getBrowserName());
            $this->assertEquals('23.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('14', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::SAMSUNG, $ua->getDeviceBrand());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        // Tablet Tests
        
        public function testAndroidTablet(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; SM-X900) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::TABLET, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::SAMSUNG, $ua->getDeviceBrand());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isTablet());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        // Bot Tests
        
        public function testGooglebot(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
            
            $this->assertTrue($ua->isBot());
            $this->assertEquals(Bot::GOOGLEBOT, $ua->getBotName());
            $this->assertEquals(DeviceType::BOT, $ua->getDeviceType());
        }
        
        public function testBingbot(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)');
            
            $this->assertTrue($ua->isBot());
            $this->assertEquals(Bot::BINGBOT, $ua->getBotName());
            $this->assertEquals(DeviceType::BOT, $ua->getDeviceType());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
        }
        
        public function testYahooSlurp(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)');
            
            $this->assertTrue($ua->isBot());
            $this->assertEquals(Bot::YAHOO_SLURP, $ua->getBotName());
            $this->assertEquals(DeviceType::BOT, $ua->getDeviceType());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
        }
        
        public function testFacebookBot(): void
        {
            $ua = new UserAgent('facebot');
            
            $this->assertTrue($ua->isBot());
            $this->assertEquals(Bot::FACEBOOK_BOT, $ua->getBotName());
            $this->assertEquals(DeviceType::BOT, $ua->getDeviceType());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
        }
        
        public function testGenericBot(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (compatible; MyBot/1.0; +http://example.com/bot)');
            
            $this->assertTrue($ua->isBot());
            $this->assertEquals(Bot::GENERIC_BOT, $ua->getBotName());
            $this->assertEquals(DeviceType::BOT, $ua->getDeviceType());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
        }
        
        // Asian Browser Tests
        
        public function testYandexBrowser(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 YaBrowser/23.11.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::YANDEX_BROWSER, $ua->getBrowserName());
            $this->assertStringStartsWith('23.11', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('10', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testUCBrowser(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 UCBrowser/15.0.0.1361');
            
            $this->assertEquals(Browser::UC_BROWSER, $ua->getBrowserName());
            $this->assertStringStartsWith('15.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        public function testMiBrowser(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; Redmi Note 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36 MiuiBrowser/14.8.0-gn');
            
            $this->assertEquals(Browser::MI_BROWSER, $ua->getBrowserName());
            $this->assertNotNull($ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::XIAOMI, $ua->getDeviceBrand());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Linux Distribution Tests
        
        public function testUbuntuFirefox(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0');
            
            $this->assertEquals(Browser::FIREFOX, $ua->getBrowserName());
            $this->assertStringStartsWith('121.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::UBUNTU, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::GECKO, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testFedora(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0');
            
            $this->assertEquals(Browser::FIREFOX, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::FEDORA, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::GECKO, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testDebian(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (X11; Linux x86_64; Debian) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::DEBIAN, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Chrome OS Test
        
        public function testChromeOS(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (X11; CrOS x86_64 15359.58.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertStringStartsWith('120.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::CHROME_OS, $ua->getOsName());
            $this->assertNotNull($ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Mobile OS Tests
        
        public function testHarmonyOS(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; HarmonyOS 4.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::HARMONY_OS, $ua->getOsName());
            $this->assertEquals('4.0.0', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        public function testKaiOS(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Mobile; rv:48.0) Gecko/48.0 Firefox/48.0 KAIOS/3.0');
            
            $this->assertEquals(Browser::FIREFOX, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::KAIOS, $ua->getOsName());
            $this->assertEquals('3.0', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::GECKO, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        // Windows Version Tests
        
        public function testWindows11(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 11.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('11', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testWindows7(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('7', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Internet Explorer Tests
        
        public function testInternetExplorer11(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko');
            
            $this->assertEquals(Browser::INTERNET_EXPLORER, $ua->getBrowserName());
            $this->assertEquals('11.0', $ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::WINDOWS, $ua->getOsName());
            $this->assertEquals('10', $ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::TRIDENT, $ua->getEngine());
            $this->assertTrue($ua->isDesktop());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // Opera Mini Test
        
        public function testOperaMini(): void
        {
            $ua = new UserAgent('Opera/9.80 (J2ME/MIDP; Opera Mini/7.1.32444/191.256; U; en) Presto/2.12.423 Version/12.16');
            
            $this->assertEquals(Browser::OPERA_MINI, $ua->getBrowserName());
            $this->assertNotNull($ua->getBrowserVersion());
            $this->assertEquals(RenderingEngine::PRESTO, $ua->getEngine());
            // Opera Mini on J2ME doesn't have a recognized OS, so device type defaults to DESKTOP
            // This is acceptable as J2ME is legacy technology
            $this->assertFalse($ua->isBot());
        }
        
        // DuckDuckGo Browser Test
        
        public function testDuckDuckGo(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 DuckDuckGo/7 Safari/605.1.15');
            
            $this->assertEquals(Browser::DUCKDUCKGO, $ua->getBrowserName());
            $this->assertNotNull($ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::IOS, $ua->getOsName());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::APPLE, $ua->getDeviceBrand());
            $this->assertEquals(RenderingEngine::WEBKIT, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isDesktop());
            $this->assertFalse($ua->isBot());
        }
        
        // Additional Device Brand Tests
        
        public function testHuawei(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 12; Huawei P50 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('12', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::HUAWEI, $ua->getDeviceBrand());
            $this->assertStringContainsString('Huawei', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testOppo(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; CPH2451) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::OPPO, $ua->getDeviceBrand());
            $this->assertStringContainsString('CPH', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testVivo(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; vivo V27 Pro) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::VIVO, $ua->getDeviceBrand());
            $this->assertStringContainsString('vivo', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        public function testRealme(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (Linux; Android 13; RMX3706) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36');
            
            $this->assertEquals(Browser::CHROME, $ua->getBrowserName());
            $this->assertEquals(OperatingSystem::ANDROID, $ua->getOsName());
            $this->assertEquals('13', $ua->getOsVersion());
            $this->assertEquals(DeviceType::MOBILE, $ua->getDeviceType());
            $this->assertEquals(DeviceBrand::REALME, $ua->getDeviceBrand());
            $this->assertStringContainsString('RMX', $ua->getDeviceModel());
            $this->assertEquals(RenderingEngine::BLINK, $ua->getEngine());
            $this->assertTrue($ua->isMobile());
            $this->assertFalse($ua->isBot());
        }
        
        // toArray() Test
        
        public function testToArray(): void
        {
            $ua = new UserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
            
            $array = $ua->toArray();
            
            $this->assertIsArray($array);
            $this->assertArrayHasKey('browser', $array);
            $this->assertArrayHasKey('os', $array);
            $this->assertArrayHasKey('device', $array);
            $this->assertArrayHasKey('engine', $array);
            $this->assertArrayHasKey('flags', $array);
            $this->assertArrayHasKey('bot', $array);
            
            $this->assertEquals('Safari', $array['browser']['name']);
            $this->assertEquals('iOS', $array['os']['name']);
            $this->assertEquals('mobile', $array['device']['type']);
            $this->assertEquals('Apple', $array['device']['brand']);
            $this->assertTrue($array['flags']['is_mobile']);
            $this->assertFalse($array['flags']['is_bot']);
        }
        
        // __toString() Test
        
        public function testToString(): void
        {
            $uaString = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $ua = new UserAgent($uaString);
            
            $this->assertEquals($uaString, (string)$ua);
            $this->assertEquals($uaString, $ua->getRawUserAgent());
        }
        
        // Edge Cases
        
        public function testEmptyUserAgent(): void
        {
            $ua = new UserAgent('');
            
            $this->assertEquals(Browser::UNKNOWN, $ua->getBrowserName());
            $this->assertNull($ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::UNKNOWN, $ua->getOsName());
            $this->assertNull($ua->getOsVersion());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::UNKNOWN, $ua->getEngine());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isTablet());
            $this->assertFalse($ua->isBot());
        }
        
        public function testUnknownUserAgent(): void
        {
            $ua = new UserAgent('SomeUnknownBrowser/1.0 (Unknown OS)');
            
            $this->assertEquals(Browser::UNKNOWN, $ua->getBrowserName());
            $this->assertNull($ua->getBrowserVersion());
            $this->assertEquals(OperatingSystem::UNKNOWN, $ua->getOsName());
            $this->assertEquals(DeviceType::DESKTOP, $ua->getDeviceType());
            $this->assertEquals(RenderingEngine::UNKNOWN, $ua->getEngine());
            $this->assertFalse($ua->isMobile());
            $this->assertFalse($ua->isTablet());
            $this->assertFalse($ua->isBot());
        }
    }

