<?PHP
        require 'ncc';

        $buildOutputPath = __DIR__ . DIRECTORY_SEPARATOR . '../target/release/net.nosial.dynamicalweb.ncc';
        if(getenv('NCC_BUILD_OUTPUT_PATH'))
        {
            $buildOutputPath = getenv('NCC_BUILD_OUTPUT_PATH');
        }

        if(!file_exists($buildOutputPath))
        {
            throw new Exception('Build output not found: ' . $buildOutputPath);
        }

        import($buildOutputPath);
        require_once __DIR__ . '/Fixtures/WebSessionFixture.php';
        if (!class_exists(Memcached::class))
        {
            require_once __DIR__ . '/Fixtures/MemcachedStub.php';
        }
        require_once __DIR__ . '/Fixtures/FakeMemcached.php';
