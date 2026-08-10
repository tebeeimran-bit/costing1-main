<?php

declare(strict_types=1);

// Stable router for PHP's built-in server on Windows. Laravel's bundled
// development router relies on getcwd(), which may remain the project root
// even when the document root is public, causing static assets to be routed
// through Laravel as HTML responses.
$publicPath = __DIR__.DIRECTORY_SEPARATOR.'public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$requestedFile = $publicPath.str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_file($requestedFile)) {
    return false;
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
