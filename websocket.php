<?php

if (php_sapi_name() === 'fpm-fcgi') {
    http_response_code(403);
    echo "Forbidden\n";
    exit(1);
}

$host = getenv('WSS_TCP_HOST');
$port = getenv('WSS_TCP_PORT');
$connId = getenv('WSS_CONNECTION_ID');

if (!$host || !$port || !$connId) {
    fwrite(STDERR, "Missing required environment variables\n");
    exit(1);
}

$fp = fsockopen($host, $port, $errno, $errstr, 30);
if (!$fp) {
    fwrite(STDERR, "Failed to connect to TCP bridge: $errstr ($errno)\n");
    exit(1);
}

fwrite($fp, $connId . "\n");
fflush($fp);

while (!feof($fp))
{
    $data = fread($fp, 8192);
    if ($data === false)
    {
        break;
    }
    if ($data === '')
    {
        usleep(10000);
        continue;
    }
    fwrite($fp, $data);
    fflush($fp);
}

fclose($fp);
