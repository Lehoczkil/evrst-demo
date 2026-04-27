<?php

namespace App\Providers\Filament;

use App\Auth\Perm;
use App\Filament\Auth\ForceChangeProfile;
use App\Filament\Widgets\AdminStatsOverview;
use App\Filament\Widgets\MyTasksWidget;
use App\Filament\Widgets\RecentApplicationsWidget;
use App\Filament\Widgets\UpcomingScheduleWidget;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Blade;
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
            ->navigationGroups([
                NavigationGroup::make('Site')      ->label(fn () => __('admin.nav.site')),
                NavigationGroup::make('About')     ->label(fn () => __('admin.nav.about')),
                NavigationGroup::make('Team')      ->label(fn () => __('admin.nav.team')),
                NavigationGroup::make('Tasks')     ->label(fn () => __('admin.nav.tasks')),
                NavigationGroup::make('Membership')->label(fn () => __('admin.nav.membership')),
                NavigationGroup::make('Advanced')  ->label(fn () => __('admin.nav.advanced')),
            ])
            ->databaseNotifications(fn () => auth()->user()?->can(Perm::NOTIFICATIONS_SEE) ?? false)
            // 30 s polling on every open tab, every user, was the largest
            // background load on the panel; 2 min is plenty for human-paced
            // notifications and cuts the request volume by 4x.
            ->databaseNotificationsPolling('2m')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                AdminStatsOverview::class,
                UpcomingScheduleWidget::class,
                MyTasksWidget::class,
                RecentApplicationsWidget::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => Blade::render('<style>
                    .fi-topbar { backdrop-filter: blur(8px); }
                    .fi-sidebar-nav-groups { gap: 0.4rem; }
                    .fi-section { box-shadow: 0 1px 2px rgba(15,23,42,.05); }
                    .fi-page-header-heading { letter-spacing: -0.01em; }
                </style>'),
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
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_HEADING_AFTER,
                fn () => view('filament.hooks.help-button'),
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
