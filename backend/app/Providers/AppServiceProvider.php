<?php

namespace App\Providers;

use App\Models\Cms\Event;
use App\Models\MemberApplication;
use App\Models\Role;
use App\Models\Task;
use App\Models\TeamMemberGroup;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->wireWidgetCacheInvalidation();
        $this->wireSelectOptionsCacheInvalidation();
        $this->configureDatePickerDefaults();
        $this->wirePasswordResetStamp();
    }

    /**
     * A completed self-service password reset is a genuine password change,
     * so stamp password_changed_at. Without this a freshly-provisioned user
     * who resets their password would still have a null timestamp and get
     * bounced straight back to /admin/profile by RequirePasswordChange —
     * asked to change the password they just set. (ForceChangeProfile does
     * the equivalent stamp for the in-panel profile form.)
     */
    private function wirePasswordResetStamp(): void
    {
        // NB: the `Event` short name in this file is the CMS model, so the
        // event dispatcher facade is referenced fully-qualified here.
        \Illuminate\Support\Facades\Event::listen(PasswordReset::class, function (PasswordReset $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['password_changed_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Native browser date inputs render a locale-dependent placeholder
     * (e.g. "yyyy. mm. dd., --:--") that doesn't match the rest of the
     * mission-console chrome. Force every Filament date / datetime
     * picker to use the JS picker with an ISO display format and a
     * spelled-out placeholder.
     */
    private function configureDatePickerDefaults(): void
    {
        DatePicker::configureUsing(fn (DatePicker $p) => $p
            ->native(false)
            ->displayFormat('Y-m-d')
            ->placeholder('YYYY-MM-DD'));

        DateTimePicker::configureUsing(fn (DateTimePicker $p) => $p
            ->native(false)
            ->displayFormat('Y-m-d H:i')
            ->placeholder('YYYY-MM-DD HH:MM'));
    }

    /**
     * Drop the dashboard widget caches whenever an underlying row
     * changes — so a 60 s TTL never shows stale numbers after an
     * admin edits something. Each saved/deleted hook is one Cache::forget,
     * which is cheap on the database driver we ship with.
     */
    private function wireWidgetCacheInvalidation(): void
    {
        $forget = function (array $keys): void {
            foreach ($keys as $k) {
                Cache::forget($k);
            }
        };

        MemberApplication::saved(fn () => $forget([
            'widgets:admin-stats',
            'widgets:recent-applications',
            'nav:applications-pending-count',
        ]));
        MemberApplication::deleted(fn () => $forget([
            'widgets:admin-stats',
            'widgets:recent-applications',
            'nav:applications-pending-count',
        ]));

        Task::saved(function (Task $task) use ($forget) {
            $forget(['widgets:admin-stats', 'widgets:upcoming-schedule']);
            // My-tasks is per-user; bust supervisor + every assignee.
            $userIds = collect([$task->supervisor_id])
                ->merge($task->assignees()->pluck('users.id'))
                ->filter()->unique();
            foreach ($userIds as $uid) {
                Cache::forget("widgets:my-tasks:{$uid}");
            }
        });
        Task::deleted(fn () => $forget(['widgets:admin-stats', 'widgets:upcoming-schedule']));

        Event::saved(fn () => $forget(['widgets:admin-stats', 'widgets:upcoming-schedule']));
        Event::deleted(fn () => $forget(['widgets:admin-stats', 'widgets:upcoming-schedule']));

        User::saved(fn () => $forget(['widgets:admin-stats']));
        User::deleted(fn () => $forget(['widgets:admin-stats']));
    }

    /**
     * The user / role / position selects on every form open were
     * re-running the same ORDER BY name pluck against the DB. Cache
     * them for 5 min and bust on save.
     */
    private function wireSelectOptionsCacheInvalidation(): void
    {
        User::saved(function () {
            Cache::forget('options:users');
            Cache::forget('options:assignees');
        });
        User::deleted(function () {
            Cache::forget('options:users');
            Cache::forget('options:assignees');
        });
        Role::saved(fn () => Cache::forget('options:roles'));
        TeamMemberGroup::saved(fn () => Cache::forget('options:team-member-groups'));
        TeamMemberGroup::deleted(fn () => Cache::forget('options:team-member-groups'));
    }
}
