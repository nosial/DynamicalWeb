<?php

    namespace DynamicalWeb\Classes;

    use DynamicalWeb\Objects\WebConfiguration\Route;
    use PHPUnit\Framework\TestCase;
    use ReflectionClass;
    use ReflectionMethod;

    class RouterTest extends TestCase
    {
        private ReflectionMethod $convertRouteToRegex;
        private ReflectionMethod $normalizePath;
        private ReflectionMethod $isMethodAllowed;
        private ReflectionMethod $matchRouteAndExtractParameters;

        protected function setUp(): void
        {
            $reflection = new ReflectionClass(Router::class);

            $this->convertRouteToRegex = $reflection->getMethod('convertRouteToRegex');
            $this->convertRouteToRegex->setAccessible(true);

            $this->normalizePath = $reflection->getMethod('normalizePath');
            $this->normalizePath->setAccessible(true);

            $this->isMethodAllowed = $reflection->getMethod('isMethodAllowed');
            $this->isMethodAllowed->setAccessible(true);

            $this->matchRouteAndExtractParameters = $reflection->getMethod('matchRouteAndExtractParameters');
            $this->matchRouteAndExtractParameters->setAccessible(true);

            // Clear the static regex cache between tests
            $regexCache = $reflection->getProperty('regexCache');
            $regexCache->setAccessible(true);
            $regexCache->setValue(null, []);
        }

        // convertRouteToRegex tests

        public function testStaticRouteRegex(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/about');
            $this->assertMatchesRegularExpression($regex, '/about');
            $this->assertDoesNotMatchRegularExpression($regex, '/about/extra');
            $this->assertDoesNotMatchRegularExpression($regex, '/other');
        }

        public function testRootRouteRegex(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/');
            $this->assertMatchesRegularExpression($regex, '/');
            $this->assertDoesNotMatchRegularExpression($regex, '/anything');
        }

        public function testSingleParameterRegex(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/users/{id}');
            $this->assertMatchesRegularExpression($regex, '/users/123');
            $this->assertMatchesRegularExpression($regex, '/users/john');
            $this->assertDoesNotMatchRegularExpression($regex, '/users/');
            $this->assertDoesNotMatchRegularExpression($regex, '/users/123/extra');
        }

        public function testMultipleParametersRegex(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/users/{id}/posts/{postId}');
            $this->assertMatchesRegularExpression($regex, '/users/42/posts/100');
            $this->assertDoesNotMatchRegularExpression($regex, '/users/42/posts/');
            $this->assertDoesNotMatchRegularExpression($regex, '/users//posts/100');
        }

        public function testIntConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/users/{id:int}');
            $this->assertMatchesRegularExpression($regex, '/users/123');
            $this->assertMatchesRegularExpression($regex, '/users/0');
            $this->assertDoesNotMatchRegularExpression($regex, '/users/abc');
            $this->assertDoesNotMatchRegularExpression($regex, '/users/12.3');
        }

        public function testIntegerConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/items/{id:integer}');
            $this->assertMatchesRegularExpression($regex, '/items/456');
            $this->assertDoesNotMatchRegularExpression($regex, '/items/abc');
        }

        public function testNumConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/page/{num:num}');
            $this->assertMatchesRegularExpression($regex, '/page/1');
            $this->assertDoesNotMatchRegularExpression($regex, '/page/abc');
        }

        public function testNumberConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/page/{num:number}');
            $this->assertMatchesRegularExpression($regex, '/page/99');
            $this->assertDoesNotMatchRegularExpression($regex, '/page/abc');
        }

        public function testAlphaConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/lang/{code:alpha}');
            $this->assertMatchesRegularExpression($regex, '/lang/en');
            $this->assertMatchesRegularExpression($regex, '/lang/French');
            $this->assertDoesNotMatchRegularExpression($regex, '/lang/en1');
            $this->assertDoesNotMatchRegularExpression($regex, '/lang/123');
        }

        public function testAlnumConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/code/{token:alnum}');
            $this->assertMatchesRegularExpression($regex, '/code/abc123');
            $this->assertMatchesRegularExpression($regex, '/code/ABC');
            $this->assertMatchesRegularExpression($regex, '/code/123');
            $this->assertDoesNotMatchRegularExpression($regex, '/code/abc-123');
        }

        public function testAlphanumericConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/ref/{code:alphanumeric}');
            $this->assertMatchesRegularExpression($regex, '/ref/abc123');
            $this->assertDoesNotMatchRegularExpression($regex, '/ref/abc_123');
        }

        public function testUuidConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/items/{uuid:uuid}');
            $this->assertMatchesRegularExpression($regex, '/items/550e8400-e29b-41d4-a716-446655440000');
            $this->assertMatchesRegularExpression($regex, '/items/A550E840-E29B-41D4-A716-446655440000');
            $this->assertDoesNotMatchRegularExpression($regex, '/items/not-a-uuid');
            $this->assertDoesNotMatchRegularExpression($regex, '/items/550e8400');
        }

        public function testSlugConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/blog/{slug:slug}');
            $this->assertMatchesRegularExpression($regex, '/blog/my-first-post');
            $this->assertMatchesRegularExpression($regex, '/blog/hello');
            $this->assertMatchesRegularExpression($regex, '/blog/post123');
            $this->assertDoesNotMatchRegularExpression($regex, '/blog/My-Post');
            $this->assertDoesNotMatchRegularExpression($regex, '/blog/post_name');
        }

        public function testCustomConstraint(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/files/{ext:[a-z]{2,4}}');
            $this->assertMatchesRegularExpression($regex, '/files/php');
            $this->assertMatchesRegularExpression($regex, '/files/html');
            $this->assertDoesNotMatchRegularExpression($regex, '/files/a');
            $this->assertDoesNotMatchRegularExpression($regex, '/files/toolong');
        }

        public function testRegexCaching(): void
        {
            $regex1 = $this->convertRouteToRegex->invoke(null, '/cached/{id}');
            $regex2 = $this->convertRouteToRegex->invoke(null, '/cached/{id}');
            $this->assertEquals($regex1, $regex2);
        }

        public function testSpecialCharactersInRouteAreEscaped(): void
        {
            $regex = $this->convertRouteToRegex->invoke(null, '/path/with.dot');
            $this->assertMatchesRegularExpression($regex, '/path/with.dot');
            $this->assertDoesNotMatchRegularExpression($regex, '/path/withXdot');
        }

        // normalizePath tests

        public function testNormalizePathRemovesBasePath(): void
        {
            $result = $this->normalizePath->invoke(null, '/app/users', '/app');
            $this->assertEquals('/users', $result);
        }

        public function testNormalizePathEmptyBasePath(): void
        {
            $result = $this->normalizePath->invoke(null, '/users', '');
            $this->assertEquals('/users', $result);
        }

        public function testNormalizePathRemovesTrailingSlash(): void
        {
            $result = $this->normalizePath->invoke(null, '/users/', '');
            $this->assertEquals('/users', $result);
        }

        public function testNormalizePathPreservesRoot(): void
        {
            $result = $this->normalizePath->invoke(null, '/', '');
            $this->assertEquals('/', $result);
        }

        public function testNormalizePathAddsLeadingSlash(): void
        {
            $result = $this->normalizePath->invoke(null, 'users', '');
            $this->assertEquals('/users', $result);
        }

        public function testNormalizePathBasePathStripping(): void
        {
            $result = $this->normalizePath->invoke(null, '/api/v1/users', '/api/v1');
            $this->assertEquals('/users', $result);
        }

        // isMethodAllowed tests

        public function testWildcardAllowsAll(): void
        {
            $route = new Route(['path' => '/', 'module' => 'index.phtml']);
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'GET'));
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'POST'));
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'DELETE'));
        }

        public function testSpecificMethodsAllowed(): void
        {
            $route = new Route([
                'path' => '/',
                'module' => 'index.phtml',
                'allowed_methods' => ['GET', 'POST'],
            ]);
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'GET'));
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'POST'));
            $this->assertFalse($this->isMethodAllowed->invoke(null, $route, 'DELETE'));
            $this->assertFalse($this->isMethodAllowed->invoke(null, $route, 'PUT'));
        }

        public function testSingleMethodAllowed(): void
        {
            $route = new Route([
                'path' => '/',
                'module' => 'index.phtml',
                'allowed_methods' => ['GET'],
            ]);
            $this->assertTrue($this->isMethodAllowed->invoke(null, $route, 'GET'));
            $this->assertFalse($this->isMethodAllowed->invoke(null, $route, 'POST'));
        }

        // matchRouteAndExtractParameters tests

        public function testMatchStaticRoute(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/about', '/about');
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }

        public function testMatchSingleParameter(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/users/{id}', '/users/42');
            $this->assertNotNull($result);
            $this->assertEquals('42', $result['id']);
        }

        public function testMatchMultipleParameters(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/users/{id}/posts/{postId}', '/users/5/posts/100');
            $this->assertNotNull($result);
            $this->assertEquals('5', $result['id']);
            $this->assertEquals('100', $result['postId']);
        }

        public function testMatchWithConstraint(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/users/{id:int}', '/users/42');
            $this->assertNotNull($result);
            $this->assertEquals('42', $result['id']);
        }

        public function testMatchWithConstraintFails(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/users/{id:int}', '/users/abc');
            $this->assertNull($result);
        }

        public function testMatchNoMatch(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/users/{id}', '/posts/42');
            $this->assertNull($result);
        }

        public function testMatchUrlDecodesParameters(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null, '/search/{query}', '/search/hello%20world');
            $this->assertNotNull($result);
            $this->assertEquals('hello world', $result['query']);
        }

        public function testMatchUuidParameter(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null,
                '/items/{uuid:uuid}',
                '/items/550e8400-e29b-41d4-a716-446655440000'
            );
            $this->assertNotNull($result);
            $this->assertEquals('550e8400-e29b-41d4-a716-446655440000', $result['uuid']);
        }

        public function testMatchSlugParameter(): void
        {
            $result = $this->matchRouteAndExtractParameters->invoke(null,
                '/blog/{slug:slug}',
                '/blog/my-first-post'
            );
            $this->assertNotNull($result);
            $this->assertEquals('my-first-post', $result['slug']);
        }
    }
