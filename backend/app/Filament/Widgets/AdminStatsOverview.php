<?php

namespace App\Filament\Widgets;

use App\Models\Cms\Event;
use App\Models\MemberApplication;
use App\Models\Task;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStatsOverview extends BaseWidget
{
    protected ?string $heading = null;

    // Stats refresh on page revisit; no need to hammer the DB every 30s.
    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $pendingApps = MemberApplication::where('status', MemberApplication::STATUS_PENDING)->count();
        $openTasks = Task::whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS])->count();
        $upcomingEvents = Event::whereNotNull('payload->start_at')
            ->where('payload->start_at', '>=', now()->toDateTimeString())
            ->count();
        $teamSize = User::whereHas('role')->count();

        return [
            Stat::make(__('admin.widgets.pending_apps'), (string) $pendingApps)
                ->description($pendingApps === 0 ? __('admin.widgets.inbox_clear') : __('admin.widgets.awaiting_review'))
                ->descriptionIcon($pendingApps === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-down-right')
                ->color($pendingApps === 0 ? 'success' : 'warning')
                ->chart([0, 0, max(0, $pendingApps - 2), $pendingApps]),
            Stat::make(__('admin.widgets.open_tasks'), (string) $openTasks)
                ->description(__('admin.widgets.open_tasks_desc'))
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($openTasks > 10 ? 'warning' : 'primary')
                ->chart([0, 1, 2, $openTasks > 0 ? min($openTasks, 6) : 0, $openTasks]),
            Stat::make(__('admin.widgets.upcoming_events'), (string) $upcomingEvents)
                ->description(__('admin.widgets.from_calendar'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([0, $upcomingEvents > 0 ? 1 : 0, $upcomingEvents]),
            Stat::make(__('admin.widgets.team_accounts'), (string) $teamSize)
                ->description(__('admin.widgets.roles_assigned'))
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray')
                ->chart([1, 1, 2, 2, $teamSize]),
        ];
    }
}
