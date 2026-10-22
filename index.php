<?php
/**
 * Root entry point.
 *
 * Use this when your web server's document root is the project root
 * instead of the "public" directory. It simply forwards to the
 * real application entry point.
 *
 * Recommended: point your document root at D:\ishema\public
 */
$publicEntry = __DIR__ . '/public/index.php';

if (is_file($publicEntry)) {
    require $publicEntry;
} else {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ISHEMA: public entry point not found. Expected ' . str_replace('/', DIRECTORY_SEPARATOR, $publicEntry);
}
