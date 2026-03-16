<?php

    namespace DynamicalWeb\Objects;

    use DynamicalWeb\Enums\MimeType;
    use DynamicalWeb\Enums\ResponseCode;
    use DynamicalWeb\Enums\ResponseType;
    use InvalidArgumentException;
    use PHPUnit\Framework\TestCase;

    class ResponseTest extends TestCase
    {
        // Constructor defaults

        public function testDefaultStatusCode(): void
        {
            $response = new Response();
            $this->assertEquals(ResponseCode::OK, $response->getStatusCode());
        }

        public function testDefaultHttpVersion(): void
        {
            $response = new Response();
            $this->assertEquals('1.1', $response->getHttpVersion());
        }

        public function testDefaultContentType(): void
        {
            $response = new Response();
            $this->assertEquals('text/html', $response->getContentType());
        }

        public function testDefaultCharset(): void
        {
            $response = new Response();
            $this->assertEquals('UTF-8', $response->getCharset());
        }

        public function testDefaultBody(): void
        {
            $response = new Response();
            $this->assertEquals('', $response->getBody());
        }

        public function testDefaultResponseType(): void
        {
            $response = new Response();
            $this->assertEquals(ResponseType::BASIC, $response->getResponseType());
        }

        public function testDefaultHeaders(): void
        {
            $response = new Response();
            $this->assertEmpty($response->getHeaders());
        }

        public function testDefaultCookies(): void
        {
            $response = new Response();
            $this->assertEmpty($response->getCookies());
        }

        public function testDefaultFilePath(): void
        {
            $response = new Response();
            $this->assertNull($response->getFilePath());
        }

        public function testDefaultStreamCallback(): void
        {
            $response = new Response();
            $this->assertNull($response->getStreamCallback());
        }

        // Status code

        public function testSetStatusCodeWithEnum(): void
        {
            $response = new Response();
            $result = $response->setStatusCode(ResponseCode::NOT_FOUND);
            $this->assertEquals(ResponseCode::NOT_FOUND, $response->getStatusCode());
            $this->assertSame($response, $result);
        }

        public function testSetStatusCodeWithValidInt(): void
        {
            $response = new Response();
            $response->setStatusCode(201);
            $this->assertEquals(ResponseCode::CREATED, $response->getStatusCode());
        }

        public function testSetStatusCodeWithInvalidIntThrows(): void
        {
            $this->expectException(InvalidArgumentException::class);
            $response = new Response();
            $response->setStatusCode(999);
        }

        // HTTP version

        public function testSetHttpVersion(): void
        {
            $response = new Response();
            $result = $response->setHttpVersion('2');
            $this->assertEquals('2', $response->getHttpVersion());
            $this->assertSame($response, $result);
        }

        // Headers

        public function testSetHeaderReplace(): void
        {
            $response = new Response();
            $response->setHeader('X-Custom', 'value1');
            $response->setHeader('X-Custom', 'value2');
            $this->assertEquals('value2', $response->getHeaders()['X-Custom']);
        }

        public function testSetHeaderAppend(): void
        {
            $response = new Response();
            $response->setHeader('X-Custom', 'value1');
            $response->setHeader('X-Custom', 'value2', false);
            $this->assertEquals(['value1', 'value2'], $response->getHeaders()['X-Custom']);
        }

        public function testSetHeaderAppendThirdValue(): void
        {
            $response = new Response();
            $response->setHeader('Vary', 'Accept');
            $response->setHeader('Vary', 'Accept-Encoding', false);
            $response->setHeader('Vary', 'Accept-Language', false);
            $this->assertEquals(['Accept', 'Accept-Encoding', 'Accept-Language'], $response->getHeaders()['Vary']);
        }

        public function testSetHeaderAppendNewKey(): void
        {
            $response = new Response();
            $response->setHeader('X-New', 'first', false);
            $this->assertEquals('first', $response->getHeaders()['X-New']);
        }

        public function testRemoveHeader(): void
        {
            $response = new Response();
            $response->setHeader('X-Remove', 'value');
            $response->removeHeader('X-Remove');
            $this->assertArrayNotHasKey('X-Remove', $response->getHeaders());
        }

        public function testSetHeaders(): void
        {
            $response = new Response();
            $headers = ['X-A' => 'a', 'X-B' => 'b'];
            $response->setHeaders($headers);
            $this->assertEquals($headers, $response->getHeaders());
        }

        // CRLF injection prevention

        public function testSetHeaderStripsCarriageReturn(): void
        {
            $response = new Response();
            $response->setHeader("X-Injected\rHeader", "value\rwith\rcr");
            $headers = $response->getHeaders();
            $this->assertArrayHasKey('X-InjectedHeader', $headers);
            $this->assertEquals('valuewithcr', $headers['X-InjectedHeader']);
        }

        public function testSetHeaderStripsNewline(): void
        {
            $response = new Response();
            $response->setHeader("X-Test", "value\nSet-Cookie: evil=1");
            $this->assertEquals('valueSet-Cookie: evil=1', $response->getHeaders()['X-Test']);
        }

        public function testSetHeaderStripsCRLF(): void
        {
            $response = new Response();
            $response->setHeader("X-Test", "value\r\nSet-Cookie: evil=1");
            $this->assertEquals('valueSet-Cookie: evil=1', $response->getHeaders()['X-Test']);
        }

        public function testSetHeaderStripsNullByte(): void
        {
            $response = new Response();
            $response->setHeader("X-Test", "value\0hidden");
            $this->assertEquals('valuehidden', $response->getHeaders()['X-Test']);
        }

        public function testSetContentTypeSanitizesCRLF(): void
        {
            $response = new Response();
            $response->setContentType("text/html\r\nX-Injected: yes");
            $this->assertEquals('text/htmlX-Injected: yes', $response->getContentType());
        }

        public function testSetCharsetSanitizesCRLF(): void
        {
            $response = new Response();
            $response->setCharset("UTF-8\r\nX-Injected: yes");
            $this->assertEquals('UTF-8X-Injected: yes', $response->getCharset());
        }

        // Content type

        public function testSetContentTypeString(): void
        {
            $response = new Response();
            $response->setContentType('application/json');
            $this->assertEquals('application/json', $response->getContentType());
        }

        public function testSetContentTypeMimeTypeEnum(): void
        {
            $response = new Response();
            $response->setContentType(MimeType::JSON);
            $this->assertEquals('application/json', $response->getContentType());
        }

        // Body

        public function testSetBody(): void
        {
            $response = new Response();
            $result = $response->setBody('<h1>Hello</h1>');
            $this->assertEquals('<h1>Hello</h1>', $response->getBody());
            $this->assertSame($response, $result);
        }

        // Charset

        public function testSetCharset(): void
        {
            $response = new Response();
            $response->setCharset('ISO-8859-1');
            $this->assertEquals('ISO-8859-1', $response->getCharset());
        }

        // JSON response

        public function testSetJson(): void
        {
            $response = new Response();
            $data = ['status' => 'ok', 'count' => 42];
            $result = $response->setJson($data);

            $this->assertEquals(ResponseType::JSON, $response->getResponseType());
            $this->assertEquals('application/json', $response->getContentType());
            $this->assertEquals(json_encode($data), $response->getBody());
            $this->assertSame($response, $result);
        }

        public function testSetJsonWithFlags(): void
        {
            $response = new Response();
            $data = ['key' => 'value'];
            $response->setJson($data, JSON_PRETTY_PRINT);
            $this->assertEquals(json_encode($data, JSON_PRETTY_PRINT), $response->getBody());
        }

        // YAML response

        public function testSetYaml(): void
        {
            $response = new Response();
            $data = ['name' => 'test', 'version' => '1.0'];
            $result = $response->setYaml($data);

            $this->assertEquals(ResponseType::YAML, $response->getResponseType());
            $this->assertEquals('application/yaml', $response->getContentType());
            $this->assertStringContainsString('name: test', $response->getBody());
            $this->assertSame($response, $result);
        }

        // Redirect response

        public function testSetRedirectDefault302(): void
        {
            $response = new Response();
            $result = $response->setRedirect('https://example.com');

            $this->assertEquals(ResponseType::REDIRECT, $response->getResponseType());
            $this->assertEquals(ResponseCode::FOUND, $response->getStatusCode());
            $this->assertEquals('https://example.com', $response->getHeaders()['Location']);
            $this->assertSame($response, $result);
        }

        public function testSetRedirectCustomStatusCode(): void
        {
            $response = new Response();
            $response->setRedirect('/new-location', ResponseCode::MOVED_PERMANENTLY);

            $this->assertEquals(ResponseCode::MOVED_PERMANENTLY, $response->getStatusCode());
            $this->assertEquals('/new-location', $response->getHeaders()['Location']);
        }

        // Stream response

        public function testSetStream(): void
        {
            $callback = function () { return 'data'; };
            $response = new Response();
            $result = $response->setStream($callback);

            $this->assertEquals(ResponseType::STREAM, $response->getResponseType());
            $this->assertSame($callback, $response->getStreamCallback());
            $this->assertEquals('no-cache', $response->getHeaders()['Cache-Control']);
            $this->assertEquals('no', $response->getHeaders()['X-Accel-Buffering']);
            $this->assertSame($response, $result);
        }

        // Cookie management

        public function testSetCookie(): void
        {
            $response = new Response();
            $result = $response->setCookie('session', 'abc123', 3600, '/', 'example.com', true, true);

            $cookie = $response->getCookie('session');
            $this->assertInstanceOf(Cookie::class, $cookie);
            $this->assertEquals('session', $cookie->getName());
            $this->assertEquals('abc123', $cookie->getValue());
            $this->assertEquals(3600, $cookie->getExpires());
            $this->assertSame($response, $result);
        }

        public function testAddCookie(): void
        {
            $response = new Response();
            $cookie = new Cookie('test', 'value', 0, '/', '', false, false, 'Strict');
            $response->addCookie($cookie);

            $this->assertSame($cookie, $response->getCookie('test'));
        }

        public function testRemoveCookie(): void
        {
            $response = new Response();
            $response->setCookie('remove_me', 'value');
            $response->removeCookie('remove_me');
            $this->assertNull($response->getCookie('remove_me'));
        }

        public function testGetCookieReturnsNullForMissing(): void
        {
            $response = new Response();
            $this->assertNull($response->getCookie('nonexistent'));
        }

        public function testSetCookies(): void
        {
            $response = new Response();
            $cookies = [
                'a' => new Cookie('a', '1'),
                'b' => new Cookie('b', '2'),
            ];
            $response->setCookies($cookies);
            $this->assertCount(2, $response->getCookies());
            $this->assertSame($cookies['a'], $response->getCookie('a'));
        }

        // Response type

        public function testSetResponseType(): void
        {
            $response = new Response();
            $result = $response->setResponseType(ResponseType::FILE_DOWNLOAD);
            $this->assertEquals(ResponseType::FILE_DOWNLOAD, $response->getResponseType());
            $this->assertSame($response, $result);
        }

        // File download (using a real file)

        public function testSetFileDownload(): void
        {
            $response = new Response();
            $filePath = __FILE__;
            $result = $response->setFileDownload($filePath, 'test.php');

            $this->assertEquals(ResponseType::FILE_DOWNLOAD, $response->getResponseType());
            $this->assertEquals($filePath, $response->getFilePath());
            $this->assertStringContainsString('test.php', $response->getHeaders()['Content-Disposition']);
            $this->assertSame($response, $result);
        }

        public function testSetFileDownloadUsesBasenameWhenNoFilename(): void
        {
            $response = new Response();
            $response->setFileDownload(__FILE__);
            $this->assertStringContainsString('ResponseTest.php', $response->getHeaders()['Content-Disposition']);
        }

        public function testSetFileDownloadThrowsForMissingFile(): void
        {
            $this->expectException(InvalidArgumentException::class);
            $response = new Response();
            $response->setFileDownload('/nonexistent/file.txt');
        }

        public function testSetFileDownloadSanitizesFilename(): void
        {
            $response = new Response();
            $response->setFileDownload(__FILE__, "evil\r\nContent-Type: text/html");

            $disposition = $response->getHeaders()['Content-Disposition'];
            $this->assertStringNotContainsString("\r", $disposition);
            $this->assertStringNotContainsString("\n", $disposition);
        }

        public function testSetFileDownloadStripsQuotesFromFilename(): void
        {
            $response = new Response();
            $response->setFileDownload(__FILE__, 'file"name.txt');

            $disposition = $response->getHeaders()['Content-Disposition'];
            $this->assertStringNotContainsString('"name', $disposition);
        }

        // Method chaining

        public function testMethodChaining(): void
        {
            $response = new Response();
            $result = $response
                ->setStatusCode(ResponseCode::CREATED)
                ->setHttpVersion('2')
                ->setContentType('application/json')
                ->setCharset('UTF-8')
                ->setBody('{"ok":true}')
                ->setHeader('X-Custom', 'test');

            $this->assertSame($response, $result);
            $this->assertEquals(ResponseCode::CREATED, $response->getStatusCode());
            $this->assertEquals('2', $response->getHttpVersion());
            $this->assertEquals('application/json', $response->getContentType());
            $this->assertEquals('{"ok":true}', $response->getBody());
        }
    }
