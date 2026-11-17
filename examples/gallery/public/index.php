<?php

declare(strict_types=1);

use Illuminate\Http\Request;

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($path) && $path !== '/' && is_file(__DIR__.$path)) {
        return false;
    }
}

$root = dirname(__DIR__, 3);
$vendor = $root.'/vendor';
$_ENV['COMPOSER_VENDOR_DIR'] = $vendor;
$_SERVER['COMPOSER_VENDOR_DIR'] = $vendor;
putenv('COMPOSER_VENDOR_DIR='.$vendor);

foreach ([
    dirname(__DIR__).'/storage/framework/cache/data',
    dirname(__DIR__).'/storage/framework/sessions',
    dirname(__DIR__).'/storage/framework/views',
    dirname(__DIR__).'/storage/framework/gallery',
    dirname(__DIR__).'/storage/logs',
    dirname(__DIR__).'/bootstrap/cache',
] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0775, true);
    }
}

require $vendor.'/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';

$app->handleRequest(Request::capture());
