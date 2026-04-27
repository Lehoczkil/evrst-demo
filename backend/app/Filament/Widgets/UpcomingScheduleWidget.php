<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Cms\Events\EventResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Cms\Event;
use App\Models\Task;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UpcomingScheduleWidget extends Widget
{
    protected string $view = 'filament.widgets.upcoming-schedule';

    public function getHeading(): ?string { return __('admin.widgets.upcoming'); }

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 'full';

    /** @return Collection<int, array<string, mixed>> */
    public function getUpcoming(): Collection
    {
        return Cache::remember('widgets:upcoming-schedule', 60, function () {
            $from = now();
            $to = now()->addDays(7);

            $events = Event::query()
                ->whereNotNull('payload->start_at')
                ->where('payload->start_at', '>=', $from->toDateTimeString())
                ->where('payload->start_at', '<=', $to->toDateTimeString())
                ->get(['id', 'payload'])
                ->map(fn (Event $e) => [
                    'when' => $e->payload['start_at'] ?? null,
                    'type' => __('admin.resources.event.s'),
                    'color' => '#0ea5e9',
                    'title' => $e->title ?? __('admin.resources.event.s'),
                    'url' => EventResource::getUrl('edit', ['record' => $e->id]),
                ]);

            $tasks = Task::query()
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_TESTING])
                ->get(['id', 'title', 'status', 'due_date'])
                ->map(fn (Task $t) => [
                    'when' => $t->due_date?->toDateTimeString(),
                    'type' => __('admin.resources.task.s'),
                    'color' => match ($t->status) {
                        Task::STATUS_TODO => '#94a3b8',
                        Task::STATUS_IN_PROGRESS => '#f59e0b',
                        Task::STATUS_TESTING => '#0ea5e9',
                        default => '#94a3b8',
                    },
                    'title' => $t->title,
                    'url' => TaskResource::getUrl('edit', ['record' => $t->id]),
                ]);

            return $events->concat($tasks)
                ->filter(fn ($i) => filled($i['when']))
                ->sortBy('when')
                ->values()
                ->take(8);
        });
    }
}
