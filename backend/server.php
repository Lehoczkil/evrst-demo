<?php

/**
 * Routing script used by `php artisan serve` (PHP's built-in web server).
 *
 * The default behavior (returning false for any file that exists in
 * public/) serves static assets directly without invoking Laravel — which
 * means /storage/* requests bypass the HandleCors middleware. In dev we
 * want the SPA on :5173/:5175 to fetch /storage assets cross-origin, so
 * route everything under /storage through Laravel.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if (! str_starts_with($uri, '/storage/')
    && $uri !== '/'
    && file_exists(__DIR__.'/public'.$uri)
) {
    return false;
}

require_once __DIR__.'/public/index.php';
