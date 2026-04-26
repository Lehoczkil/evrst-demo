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
            Stat::make('Pending applications', (string) $pendingApps)
                ->description($pendingApps === 0 ? 'Inbox is clear' : 'Awaiting review')
                ->descriptionIcon($pendingApps === 0 ? 'heroicon-m-check-circle' : 'heroicon-m-arrow-down-right')
                ->color($pendingApps === 0 ? 'success' : 'warning')
                ->chart([0, 0, max(0, $pendingApps - 2), $pendingApps]),
            Stat::make('Open tasks', (string) $openTasks)
                ->description('To-do + in-progress')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($openTasks > 10 ? 'warning' : 'primary')
                ->chart([0, 1, 2, $openTasks > 0 ? min($openTasks, 6) : 0, $openTasks]),
            Stat::make('Upcoming events', (string) $upcomingEvents)
                ->description('From the calendar')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->chart([0, $upcomingEvents > 0 ? 1 : 0, $upcomingEvents]),
            Stat::make('Team accounts', (string) $teamSize)
                ->description('Roles assigned')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('gray')
                ->chart([1, 1, 2, 2, $teamSize]),
        ];
    }
}
