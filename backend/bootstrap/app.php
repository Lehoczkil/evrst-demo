<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        // Caddy / Fly / any PaaS terminates TLS in front of the
        // container. Trust everything so Laravel reads the correct
        // scheme + client IP from X-Forwarded-* headers — otherwise
        // Filament generates http:// URLs that the browser blocks
        // because the page itself was served over https://.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
