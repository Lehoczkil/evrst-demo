<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Forces every authenticated admin user with a null password_changed_at
 * to set a new password before reaching any other admin page.
 *
 * The seeded admin gets password_changed_at=now() so it never trips this
 * middleware. Anyone provisioned via the Accept flow / UserResource is
 * created with password_changed_at=null.
 */
class RequirePasswordChange
{
    private const ALLOWED_ROUTE_PREFIXES = [
        'filament.admin.auth.',  // login, logout, password-reset, profile
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $user->password_changed_at !== null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? '';
        foreach (self::ALLOWED_ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return $next($request);
            }
        }

        // Livewire updates from the profile form must pass through.
        if (str_starts_with($request->path(), 'livewire/')) {
            return $next($request);
        }

        if (\Filament\Facades\Filament::hasProfile()) {
            return redirect(\Filament\Facades\Filament::getProfileUrl());
        }

        return $next($request);
    }
}
