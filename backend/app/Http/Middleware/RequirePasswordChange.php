<?php

namespace App\Http\Middleware;

use App\Filament\Auth\ForceChangeProfile;
use Closure;
use Filament\Facades\Filament;
use Filament\Livewire\DatabaseNotifications;
use Filament\Livewire\Notifications;
use Illuminate\Http\Request;
use Livewire\Mechanisms\ComponentRegistry;
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

    /**
     * Livewire paths that carry no component of their own: the asset
     * itself, and the upload endpoints the profile form's avatar field
     * posts to.
     */
    private const ALLOWED_LIVEWIRE_PATHS = [
        'livewire/livewire.js',
        'livewire/livewire.min.js.map',
        'livewire/update',
        'livewire/upload-file',
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

        if (str_starts_with($request->path(), 'livewire/')) {
            return $this->handleLivewire($request, $next);
        }

        return $this->bounce($next, $request);
    }

    /**
     * The profile form is a Livewire component, so its update requests have
     * to pass — but letting through everything under livewire/ handed an
     * unactivated user the rest of the panel's components as well, the
     * global search among them: they could list records from the profile
     * page's topbar before setting a password. Allow only the components
     * that page is actually made of.
     */
    private function handleLivewire(Request $request, Closure $next): Response
    {
        if (! in_array($request->path(), self::ALLOWED_LIVEWIRE_PATHS, true)
            && ! str_starts_with($request->path(), 'livewire/preview-file/')) {
            return $this->bounce($next, $request);
        }

        $components = $request->input('components');

        // Not a component update (asset fetch, file upload) — nothing to
        // check beyond the path allow-list above.
        if (! is_array($components) || $components === []) {
            return $next($request);
        }

        $allowed = $this->allowedComponentNames();

        foreach ($components as $component) {
            $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
            $name = $snapshot['memo']['name'] ?? null;

            if (! is_string($name) || ! in_array($name, $allowed, true)) {
                return $this->bounce($next, $request);
            }
        }

        return $next($request);
    }

    /**
     * Resolved from the classes rather than hardcoded, so a Livewire or
     * Filament rename can't silently turn this gate into a lockout.
     *
     * @return array<int, string>
     */
    private function allowedComponentNames(): array
    {
        $registry = app(ComponentRegistry::class);

        return array_map(
            fn (string $class) => $registry->getName($class),
            [
                // The profile form itself — the one page they may reach.
                ForceChangeProfile::class,
                // Panel chrome the page renders: flash messages and the
                // notification bell's poll. Neither exposes any record.
                Notifications::class,
                DatabaseNotifications::class,
            ],
        );
    }

    /**
     * Send them to the profile form.
     *
     * Resolved off the panel rather than the Filament facade: the facade
     * answers for the *current* panel, and /livewire/update does not run
     * the panel middleware that sets one — so the facade reports no
     * profile page there and the gate would fall open on exactly the
     * requests it exists to check.
     */
    private function bounce(Closure $next, Request $request): Response
    {
        $panel = Filament::getCurrentPanel() ?? Filament::getDefaultPanel();

        if ($panel?->hasProfile()) {
            return redirect($panel->getProfileUrl());
        }

        return $next($request);
    }
}
