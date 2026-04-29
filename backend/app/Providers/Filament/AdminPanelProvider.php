<?php

namespace App\Providers\Filament;

use App\Auth\Perm;
use App\Filament\Auth\ForceChangeProfile;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Enums\ThemeMode;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('EVRST Admin')
            ->brandLogo(asset('evrst_logo.svg'))
            ->brandLogoHeight('1.75rem')
            ->favicon(asset('evrst_logo.svg'))
            ->login()
            ->profile(ForceChangeProfile::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->defaultThemeMode(ThemeMode::Dark)
            // Filament's NavigationManager matches a resource's
            // `$navigationGroup` string against either the array key
            // OR the registered group's evaluated label. Using a
            // closure label means the label string changes per locale
            // — in HU `getLabel()` returns "Oldal", which then fails
            // to match a resource declaring `$navigationGroup = 'Site'`,
            // and Filament silently falls back to a new untranslated
            // group named "Site". Keying by the raw English string
            // makes the match locale-independent while the label
            // closure stays free to translate.
            ->navigationGroups([
                'Site'       => NavigationGroup::make()->label(fn () => __('admin.nav.site')),
                'About'      => NavigationGroup::make()->label(fn () => __('admin.nav.about')),
                'Team'       => NavigationGroup::make()->label(fn () => __('admin.nav.team')),
                'Tasks'      => NavigationGroup::make()->label(fn () => __('admin.nav.tasks')),
                'Membership' => NavigationGroup::make()->label(fn () => __('admin.nav.membership')),
                'Advanced'   => NavigationGroup::make()->label(fn () => __('admin.nav.advanced')),
            ])
            ->databaseNotifications(fn () => auth()->user()?->can(Perm::NOTIFICATIONS_SEE) ?? false)
            // 30 s polling on every open tab, every user, was the largest
            // background load on the panel; 2 min is plenty for human-paced
            // notifications and cuts the request volume by 4x.
            ->databaseNotificationsPolling('2m')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                // Custom mission-console dashboard — countdown + 6-tile grid.
                // See app/Filament/Pages/Dashboard.php and the matching Blade.
                Dashboard::class,
            ])
            // No widgets — every dashboard panel is rendered directly by
            // the custom Dashboard view above. Cache keys (widgets:*) are
            // still invalidated by AppServiceProvider on model events.
            ->widgets([])
            ->sidebarCollapsibleOnDesktop()
            // Mission-console theme — single override stylesheet over
            // Filament's `.fi-*` selectors. See public/css/admin-theme.css
            // and docs/admin-redesign.md.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => '<link rel="stylesheet" href="' . asset('css/admin-theme.css') . '?v=' . filemtime(public_path('css/admin-theme.css')) . '" />',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => view('filament.hooks.desktop-view-script'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.hooks.desktop-view-toggle'),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.hooks.locale-switcher'),
            )
            // Report-a-bug shortcut — any signed-in user with bugs.report
            // can file a report from anywhere in the panel. Lives next
            // to the user menu so it's always reachable.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.hooks.report-bug-button'),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_HEADING_AFTER,
                fn () => view('filament.hooks.help-button'),
            )
            // Mobile-only sidebar search — CSS keeps it hidden on lg+
            // so it doesn't duplicate the topbar's global search.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                fn () => view('filament.hooks.sidebar-search'),
            )
            // Tiny version stamp pinned at the bottom of the sidebar.
            // Reads `config('app.version')` — bump that before each deploy.
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn () => view('filament.hooks.sidebar-version'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => view('filament.hooks.help-modal'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                // Honour the session-stored locale on guest screens (login,
                // password reset) so the form text matches the chosen UI.
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                // Re-runs after auth so the user's saved preference takes
                // precedence over the session fallback.
                SetLocale::class,
                RequirePasswordChange::class,
            ]);
    }
}
