<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\Cms\Event;
use App\Models\MemberApplication;
use App\Models\Task;
use App\Models\User;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Custom EVRST mission-console dashboard. Replaces Filament's stock
 * widget grid with the layout from docs/admin-redesign-mockup.html:
 * a full-width T-countdown strip + a 2-col 6-tile grid pulling real
 * data from the existing models.
 *
 * Cache keys are kept in sync with the ones invalidated in
 * AppServiceProvider so the existing model-event hooks keep working.
 */
class Dashboard extends BaseDashboard
{
    protected string $view = 'filament.pages.dashboard';

    public function getTitle(): string { return __('admin.widgets.dashboard') ?? 'Dashboard'; }

    /** Filament shows widgets by default; we render everything in the Blade. */
    public function getWidgets(): array { return []; }
    public function getVisibleWidgets(): array { return []; }
    public function getColumns(): int|array { return 1; }

    /** ────────────────────────  countdown  ──────────────────────── */

    /** @return array<string, mixed> */
    public function getMissionState(): array
    {
        $next = CalendarEvent::query()->where('start_at', '>=', now())->orderBy('start_at')->first();
        if ($next && $next->start_at) {
            return [
                'mode'    => 'countdown',
                'target'  => $next->start_at->copy()->utc()->toIso8601String(),
                'title'   => $next->title,
                'sub'     => trim(collect([$next->location, $next->start_at->format('Y-m-d H:i')])->filter()->join(' · ')),
                'standby' => false,
            ];
        }

        $last = CalendarEvent::query()->where('start_at', '<', now())->orderByDesc('start_at')->first();
        if ($last && $last->start_at) {
            return [
                'mode'    => 'sincelast',
                'target'  => $last->start_at->copy()->utc()->toIso8601String(),
                'title'   => $last->title,
                'sub'     => 'last event · ' . $last->start_at->format('Y-m-d'),
                'standby' => true,
            ];
        }

        return [
            'mode'    => 'standby',
            'target'  => Carbon::now()->utc()->toIso8601String(),
            'title'   => 'STANDBY',
            'sub'     => 'no events scheduled',
            'standby' => true,
        ];
    }

    /** ───────────────────────  data tiles  ──────────────────────── */

    /** @return array{pending: int, open: int, events: int, team: int} */
    public function getStats(): array
    {
        [$pending, $open, $events, $team] = Cache::remember('widgets:admin-stats', 60, fn () => [
            MemberApplication::where('status', MemberApplication::STATUS_PENDING)->count(),
            Task::whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS])->count(),
            Event::whereNotNull('start_at')->where('start_at', '>=', now())->count(),
            User::whereHas('role')->count(),
        ]);

        return ['pending' => $pending, 'open' => $open, 'events' => $events, 'team' => $team];
    }

    /** @return Collection<int, MemberApplication> */
    public function getPendingApplications(): Collection
    {
        return Cache::remember('widgets:recent-applications', 60, fn () =>
            MemberApplication::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'email', 'department', 'status', 'created_at'])
        );
    }

    /** @return Collection<int, Task> */
    public function getMyOpenTasks(): Collection
    {
        $userId = auth()->id();
        if (! $userId) return collect();
        return Cache::remember("widgets:my-tasks:{$userId}", 60, fn () =>
            Task::query()
                ->where(function ($q) use ($userId) {
                    $q->where('supervisor_id', $userId)
                      ->orWhereHas('assignees', fn ($q2) => $q2->where('users.id', $userId));
                })
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_TESTING])
                ->orderBy('due_date')
                ->limit(5)
                ->get(['id', 'title', 'status', 'priority', 'due_date'])
        );
    }

    /** @return Collection<int, array<string, mixed>> */
    public function getUpcomingThisWeek(): Collection
    {
        return Cache::remember('widgets:upcoming-schedule', 60, function () {
            $from = now();
            $to = now()->addDays(7);

            $events = Event::query()
                ->whereNotNull('start_at')
                ->whereBetween('start_at', [$from, $to])
                ->get(['id', 'payload', 'start_at'])
                ->map(fn (Event $e) => [
                    'when'  => $e->start_at,
                    'kind'  => 'event',
                    'color' => 'info',
                    'title' => $e->title ?? __('admin.resources.event.s'),
                    'url'   => \App\Filament\Resources\Cms\Events\EventResource::getUrl('edit', ['record' => $e->id]),
                ]);

            $cals = CalendarEvent::query()
                ->whereBetween('start_at', [$from, $to])
                ->get(['id', 'title', 'start_at'])
                ->map(fn (CalendarEvent $c) => [
                    'when'  => $c->start_at,
                    'kind'  => 'event',
                    'color' => 'amber',
                    'title' => $c->title,
                    'url'   => '/admin/calendar',
                ]);

            $tasks = Task::query()
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_TESTING])
                ->get(['id', 'title', 'status', 'due_date'])
                ->map(fn (Task $t) => [
                    'when'  => $t->due_date,
                    'kind'  => 'task',
                    'color' => match ($t->status) {
                        Task::STATUS_IN_PROGRESS => 'amber',
                        Task::STATUS_TESTING     => 'info',
                        default                  => 'muted',
                    },
                    'title' => $t->title,
                    'url'   => \App\Filament\Resources\Tasks\TaskResource::getUrl('edit', ['record' => $t->id]),
                ]);

            return $events->concat($cals)->concat($tasks)
                ->filter(fn ($i) => $i['when'] !== null)
                ->sortBy(fn ($i) => $i['when']->getTimestamp())
                ->values()
                ->take(5);
        });
    }

    /** @return Collection<int, MemberApplication> */
    public function getLatestApplications(): Collection
    {
        return Cache::remember('widgets:latest-applications', 60, fn () =>
            MemberApplication::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(['id', 'name', 'email', 'department', 'status', 'created_at'])
        );
    }

    /** @return Collection<int, ActivityLog> */
    public function getRecentActivity(): Collection
    {
        return Cache::remember('widgets:dashboard-activity', 60, fn () =>
            ActivityLog::query()
                ->with('user:id,name')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
        );
    }

    /** @return array<int, array{label: string, url: string, icon: string}> */
    public function getQuickActions(): array
    {
        return [
            [
                'label' => __('admin.resources.task.s'),
                'url'   => \App\Filament\Resources\Tasks\TaskResource::getUrl('create'),
                'icon'  => 'plus',
            ],
            [
                'label' => __('admin.resources.calendar.s'),
                'url'   => '/admin/calendar',
                'icon'  => 'plus',
            ],
            [
                'label' => __('admin.resources.drawing.s'),
                'url'   => \App\Filament\Resources\Drawings\DrawingResource::getUrl('create'),
                'icon'  => 'plus',
            ],
            [
                'label' => __('admin.resources.onshape_model.s'),
                'url'   => \App\Filament\Resources\OnshapeModels\OnshapeModelResource::getUrl('create'),
                'icon'  => 'plus',
            ],
        ];
    }
}
