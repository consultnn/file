<?php

define('STORAGE_DIR', dirname(__DIR__) . '/storage/');
define('RUNTIME_DIR', dirname(__DIR__) . '/runtime/');
define('CACHE_DIR', dirname(__DIR__) . '/web/cache/');

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}
require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../settings/config.php';
$application = (new Application());
$response = (new Application())->run($config);
$application->echoResponse($response);
