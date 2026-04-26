<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve the request locale from (in order): ?lang= query, X-Lang header,
 * Accept-Language header, then config('app.locale'). Anything outside the
 * supported list falls back to the default.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'hu'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolve($request) ?? config('app.locale');
        app()->setLocale($locale);

        return $next($request);
    }

    private function resolve(Request $request): ?string
    {
        $candidates = [
            $request->query('lang'),
            $request->header('X-Lang'),
            $this->primaryLanguage($request->header('Accept-Language')),
        ];

        foreach ($candidates as $candidate) {
            $code = is_string($candidate) ? strtolower(substr(trim($candidate), 0, 2)) : null;
            if ($code && in_array($code, self::SUPPORTED, true)) {
                return $code;
            }
        }

        return null;
    }

    private function primaryLanguage(?string $header): ?string
    {
        if (! $header) return null;
        $first = trim(explode(',', $header)[0] ?? '');
        return $first ?: null;
    }
}
