<?php

/**
 * Router script for PHP's built-in development server.
 *
 * Mimics .htaccess rewrite: serve existing files, otherwise bootstrap index.php.
 * Dynamic front-controller paths (e.g. /dist/pinoox.js) always route through PHP.
 * Used by: php pinoox serve
 */

use Pinoox\Component\Server\FrontController;

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$documentRoot = rtrim(str_replace('\\', '/', (string) getcwd()), '/');

require_once $documentRoot . '/vendor/autoload.php';

$target = $documentRoot . ($uri === '/' ? '' : $uri);

if (getenv('PINOOX_SERVER_LOG') !== '0') {
    $timestamp = date('D M j H:i:s Y');
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $remote = ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1') . ':' . ($_SERVER['REMOTE_PORT'] ?? '0');
    file_put_contents('php://stdout', "[{$timestamp}] {$remote} [{$method}] URI: {$uri}\n");
}

if (FrontController::shouldRoute($uri, $documentRoot)) {
    FrontController::applyServerGlobals($uri);
    require $documentRoot . '/index.php';

    return;
}

if ($uri !== '/' && $uri !== '' && is_file($target)) {
    return false;
}

require $documentRoot . '/index.php';
