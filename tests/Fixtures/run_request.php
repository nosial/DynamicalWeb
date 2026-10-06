<?php

    /**
     * Runs one request through DynamicalWeb::handleRequest() in a fresh process and prints the outcome as JSON.
     * Responses are sent with header()/exit, which cannot happen inside the PHPUnit process.
     *
     * Usage: php run_request.php <spec.json>
     *
     * The spec holds: config (web configuration), modules (relative path => file contents), server ($_SERVER
     * values), get, post, cookies, env and memcached (a FakeMemcached::export() the session store starts from).
     * A module can hand its Response to the runner with `$GLOBALS['dw_test_response'] = WebSession::getResponse();`
     * so headers and cookies are reported too.
     */

    use DynamicalWeb\Tests\Fixtures\FakeMemcached;
    use DynamicalWeb\Tests\Fixtures\WebSessionFixture;

    ob_start();
    require __DIR__ . '/../bootstrap.php';

    $spec = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);

    $webRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dw_request_' . bin2hex(random_bytes(6));
    mkdir($webRoot);
    foreach ($spec['modules'] ?? [] as $path => $contents)
    {
        $file = $webRoot . DIRECTORY_SEPARATOR . $path;
        if (!is_dir(dirname($file)))
        {
            mkdir(dirname($file), 0777, true);
        }
        file_put_contents($file, $contents);
    }

    foreach ($spec['env'] ?? [] as $name => $value)
    {
        putenv($value === null ? $name : $name . '=' . $value);
    }

    if (isset($spec['memcached']))
    {
        FakeMemcached::import($spec['memcached']);
    }
    FakeMemcached::attachToWebSession();

    $_SERVER = array_merge($_SERVER, ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/', 'HTTP_HOST' => 'localhost', 'REMOTE_ADDR' => '203.0.113.10', 'HTTP_USER_AGENT' => 'TestAgent/1.0'], $spec['server'] ?? []);
    $_GET = $spec['get'] ?? [];
    $_POST = $spec['post'] ?? [];
    $_COOKIE = $spec['cookies'] ?? [];
    $GLOBALS['dw_test_log'] = [];
    $GLOBALS['dw_test_response'] = null;

    register_shutdown_function(static function () use ($webRoot): void
    {
        $body = '';
        while (ob_get_level() > 0)
        {
            $body = ob_get_clean() . $body;
        }

        $response = $GLOBALS['dw_test_response'];
        $cookies = [];
        foreach ($response?->getCookies() ?? [] as $cookie)
        {
            $cookies[$cookie->getName()] = $cookie->toArray();
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($webRoot, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $file)
        {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($webRoot);

        fwrite(STDOUT, json_encode([
            'status' => http_response_code(),
            'body' => $body,
            'headers' => $response?->getHeaders(),
            'content_type' => $response?->getContentType(),
            'cookies' => $cookies,
            'log' => $GLOBALS['dw_test_log'],
        ]));
    });

    $config = $spec['config'] ?? [];
    $config['application'] = ($config['application'] ?? []) + ['disable_apcu' => true];
    WebSessionFixture::makeInstance($config, $webRoot)->handleRequest();
