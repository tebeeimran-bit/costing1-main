<?php

$requestPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$publicPath = __DIR__.str_replace('/', DIRECTORY_SEPARATOR, $requestPath);

// Biarkan PHP built-in server melayani asset statis secara langsung.
if ($requestPath !== '/' && is_file($publicPath)) {
    return false;
}

require __DIR__.'/index.php';
