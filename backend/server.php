<?php

/**
 * Routing script used by `php artisan serve` (PHP's built-in web server).
 *
 * Default behaviour: returning `false` for any file that exists in
 * `public/` makes PHP serve the asset directly instead of routing
 * through Laravel. We keep that behaviour for everything — including
 * `/storage/*`, which is a symlink that resolves to real files. The
 * Laravel `/storage/{path}` route still exists for production / for
 * the SPA's CORS needs, it just doesn't get hit in dev when the file
 * is on disk.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';
