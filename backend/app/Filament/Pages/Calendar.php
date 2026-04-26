<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Cms\AboutProjects\AboutProjectResource;
use App\Filament\Resources\Cms\Events\EventResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Cms\AboutProject;
use App\Models\Cms\Event;
use App\Models\Task;
use App\Models\User;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Cross-entity month calendar — events, projects (date ranges), and
 * tasks (due dates) on a single grid. Filterable by entity type and by
 * an assignee user.
 */
class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Tasks';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.calendar';

    public string $cursor;            // first-of-month, ISO date string
    public bool $showEvents = true;
    public bool $showProjects = true;
    public bool $showTasks = true;
    public ?int $assigneeId = null;

    public function mount(): void
    {
        $this->cursor = now()->startOfMonth()->toDateString();
    }

    public function previousMonth(): void
    {
        $this->cursor = Carbon::parse($this->cursor)->subMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $this->cursor = Carbon::parse($this->cursor)->addMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function today(): void
    {
        $this->cursor = now()->startOfMonth()->toDateString();
    }

    /** @return array<int, array{id: int, name: string}> */
    public function getAssigneeOptions(): array
    {
        return User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
        ])->all();
    }

    public function getMonthLabel(): string
    {
        return Carbon::parse($this->cursor)->translatedFormat('F Y');
    }

    /**
     * Build the day-by-day matrix for the current month. Returns 6 rows of
     * 7 days each so the grid is always rectangular regardless of how the
     * month aligns with the week.
     *
     * @return array<int, array<int, array{date: \Carbon\CarbonInterface, inMonth: bool, items: array<int, array<string, mixed>>}>>
     */
    public function getCalendarGrid(): array
    {
        $start = Carbon::parse($this->cursor)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Calendar window — extended to fill the grid (Mon-aligned).
        $gridStart = $start->copy()->startOfWeek(CarbonInterface::MONDAY);
        $gridEnd = $end->copy()->endOfWeek(CarbonInterface::SUNDAY);

        $items = $this->loadItems($gridStart, $gridEnd);

        $grouped = [];
        foreach ($items as $item) {
            $bucket = Carbon::parse($item['date'])->toDateString();
            $grouped[$bucket][] = $item;
        }

        $rows = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $row = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $cursor->toDateString();
                $row[] = [
                    'date' => $cursor->copy(),
                    'inMonth' => $cursor->month === $start->month,
                    'items' => $grouped[$key] ?? [],
                ];
                $cursor->addDay();
            }
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Pull every visible item in the window. Each item is normalised to
     * `{ type, title, date, end?, color, url, badges[] }`.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadItems(CarbonInterface $from, CarbonInterface $to): array
    {
        $items = [];

        if ($this->showEvents) {
            $events = Event::query()
                ->whereNotNull('payload->start_at')
                ->where('payload->start_at', '>=', $from->toDateTimeString())
                ->where('payload->start_at', '<=', $to->toDateTimeString())
                ->get();
            foreach ($events as $event) {
                $start = $event->payload['start_at'] ?? null;
                if (! $start) continue;
                $items[] = [
                    'type' => 'event',
                    'title' => $event->title ?? 'Event',
                    'date' => $start,
                    'end' => $event->payload['end_at'] ?? null,
                    'color' => '#0ea5e9',
                    'url' => EventResource::getUrl('edit', ['record' => $event->id]),
                    'badges' => [],
                ];
            }
        }

        if ($this->showProjects) {
            $projects = AboutProject::query()
                ->whereNotNull('payload->start_at')
                ->where('payload->start_at', '>=', $from->toDateTimeString())
                ->where('payload->start_at', '<=', $to->toDateTimeString())
                ->get();
            foreach ($projects as $project) {
                $start = $project->payload['start_at'] ?? null;
                if (! $start) continue;
                $items[] = [
                    'type' => 'project',
                    'title' => $project->title ?? 'Project',
                    'date' => $start,
                    'end' => $project->payload['end_at'] ?? null,
                    'color' => '#a855f7',
                    'url' => AboutProjectResource::getUrl('edit', ['record' => $project->id]),
                    'badges' => [],
                ];
            }
        }

        if ($this->showTasks) {
            $tasks = Task::query()
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->with(['assignees', 'supervisor'])
                ->get();
            foreach ($tasks as $task) {
                if ($this->assigneeId && ! $task->assignees->contains('id', $this->assigneeId)) {
                    continue;
                }
                $items[] = [
                    'type' => 'task',
                    'title' => $task->title,
                    'date' => $task->due_date->toDateTimeString(),
                    'end' => null,
                    'color' => match ($task->status) {
                        Task::STATUS_TODO => '#94a3b8',
                        Task::STATUS_IN_PROGRESS => '#f59e0b',
                        Task::STATUS_TESTING => '#0284c7',
                        Task::STATUS_DONE => '#10b981',
                        default => '#94a3b8',
                    },
                    'url' => TaskResource::getUrl('edit', ['record' => $task->id]),
                    'badges' => array_values(array_filter([
                        $task->supervisor?->name ? '👤 ' . $task->supervisor->name : null,
                        $task->assignees->isNotEmpty() ? '🛠 ' . $task->assignees->pluck('name')->join(', ') : null,
                    ])),
                ];
            }
        }

        usort($items, fn ($a, $b) => strcmp($a['date'], $b['date']));
        return $items;
    }

    public function canCreateEvents(): bool
    {
        return auth()->user()?->can(\App\Auth\Perm::EVENTS_CREATE) ?? false;
    }
}
