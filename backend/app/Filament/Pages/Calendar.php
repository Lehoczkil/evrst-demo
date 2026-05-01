<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Cms\AboutProjects\AboutProjectResource;
use App\Filament\Resources\Tasks\TaskResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\CalendarEvent;
use App\Models\Cms\AboutProject;
use App\Models\Task;
use App\Models\User;
use App\Support\DiscordPayloads;
use BackedEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

/**
 * Admin-only month calendar — shows {@see CalendarEvent} entries created
 * inside the panel (separate from the public-facing CMS Event collection),
 * plus project ranges and task due dates.
 *
 * Days are clickable to spawn a new event; events are clickable to edit.
 */
class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|\UnitEnum|null $navigationGroup = 'Tasks';

    public static function getNavigationLabel(): string
    {
        return __('admin.calendar.title');
    }

    public function getTitle(): string
    {
        return __('admin.calendar.title');
    }

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.calendar';

    public string $cursor;            // first-of-month, ISO date string
    public bool $showEvents = true;
    public bool $showProjects = true;
    public bool $showTasks = true;
    public ?int $assigneeId = null;

    // Modal form state
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $eventTitle = '';
    public string $eventDescription = '';
    public string $eventLocation = '';
    public string $eventStart = '';
    public string $eventEnd = '';
    public bool $eventAllDay = false;
    public string $eventColor = '#0ea5e9';

    // Past-date confirmation. When the user clicks a day that's
    // already passed, we hold the requested date here and surface a
    // small confirmation modal first instead of opening the form
    // immediately. Empty string == nothing pending.
    public string $pendingPastDate = '';

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
        return \Illuminate\Support\Facades\Cache::remember(
            'options:assignees',
            300,
            fn () => User::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->all(),
        );
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
     * `{ id, kind, type, title, date, end?, color, badges[] }`. `kind` is
     * 'event'|'project'|'task' for click-handling; 'type' is the human
     * label rendered on the card.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadItems(CarbonInterface $from, CarbonInterface $to): array
    {
        $items = [];

        if ($this->showEvents) {
            $events = CalendarEvent::query()
                ->whereBetween('start_at', [$from->toDateTimeString(), $to->toDateTimeString()])
                ->get();
            foreach ($events as $event) {
                $items[] = [
                    'id' => $event->id,
                    'kind' => 'event',
                    'type' => 'Event',
                    'title' => $event->title,
                    'date' => $event->start_at?->toDateTimeString(),
                    'end' => $event->end_at?->toDateTimeString(),
                    'all_day' => (bool) $event->all_day,
                    'color' => $event->color ?: '#0ea5e9',
                    'location' => $event->location,
                    'badges' => array_values(array_filter([
                        $event->location ? '📍 ' . $event->location : null,
                    ])),
                ];
            }
        }

        if ($this->showProjects) {
            $projects = AboutProject::query()
                ->whereNotNull('start_at')
                ->whereBetween('start_at', [$from, $to])
                ->get();
            foreach ($projects as $project) {
                if (! $project->start_at) continue;
                $items[] = [
                    'id' => $project->id,
                    'kind' => 'project',
                    'type' => 'Project',
                    'title' => $project->title ?? 'Project',
                    'date' => $project->start_at->toDateTimeString(),
                    'end' => $project->end_at?->toDateTimeString(),
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
                    'id' => $task->id,
                    'kind' => 'task',
                    'type' => 'Task',
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

        usort($items, fn ($a, $b) => strcmp((string) $a['date'], (string) $b['date']));
        return $items;
    }

    public function openCreateModal(string $date): void
    {
        // Guard against scheduling on a past day — surface a small
        // confirm dialog first. Compare on the day boundary so clicking
        // "today" never trips the gate.
        try {
            $clicked = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return;
        }
        if ($clicked->lt(now()->startOfDay())) {
            $this->pendingPastDate = $date;
            return;
        }
        $this->openCreateModalNow($date);
    }

    public function confirmPastDate(): void
    {
        if ($this->pendingPastDate === '') return;
        $date = $this->pendingPastDate;
        $this->pendingPastDate = '';
        $this->openCreateModalNow($date);
    }

    public function cancelPastDate(): void
    {
        $this->pendingPastDate = '';
    }

    private function openCreateModalNow(string $date): void
    {
        $this->resetEventForm();
        $this->editingId = null;
        // Default to a 1-hour slot at 09:00 on the clicked date.
        $this->eventStart = $date . 'T09:00';
        $this->eventEnd = $date . 'T10:00';
        $this->eventColor = '#0ea5e9';
        $this->eventTitle = '';
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $event = CalendarEvent::find($id);
        if (! $event) {
            Notification::make()->title('Event not found')->danger()->send();
            return;
        }
        $this->editingId = $event->id;
        $this->eventTitle = (string) $event->title;
        $this->eventDescription = (string) ($event->description ?? '');
        $this->eventLocation = (string) ($event->location ?? '');
        $this->eventStart = $event->start_at?->format('Y-m-d\TH:i') ?? '';
        $this->eventEnd = $event->end_at?->format('Y-m-d\TH:i') ?? '';
        $this->eventAllDay = (bool) $event->all_day;
        $this->eventColor = $event->color ?: '#0ea5e9';
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        $this->showFormModal = false;
        $this->resetEventForm();
    }

    private function resetEventForm(): void
    {
        $this->editingId = null;
        $this->eventTitle = '';
        $this->eventDescription = '';
        $this->eventLocation = '';
        $this->eventStart = '';
        $this->eventEnd = '';
        $this->eventAllDay = false;
        $this->eventColor = '#0ea5e9';
    }

    public function saveEvent(): void
    {
        $title = trim($this->eventTitle);
        if ($title === '') {
            Notification::make()->title(__('admin.calendar.modal.title_req'))->danger()->send();
            return;
        }
        if ($this->eventStart === '') {
            Notification::make()->title(__('admin.calendar.modal.start_req'))->danger()->send();
            return;
        }

        try {
            $start = Carbon::parse($this->eventStart);
            $end = $this->eventEnd !== '' ? Carbon::parse($this->eventEnd) : null;
        } catch (\Throwable) {
            Notification::make()->title(__('admin.calendar.modal.invalid_date'))->danger()->send();
            return;
        }

        if ($end && $end->lt($start)) {
            Notification::make()->title(__('admin.calendar.modal.end_after'))->danger()->send();
            return;
        }

        $payload = [
            'user_id' => auth()->id(),
            'title' => mb_substr($title, 0, 200),
            'description' => $this->eventDescription !== '' ? $this->eventDescription : null,
            'location' => $this->eventLocation !== '' ? mb_substr($this->eventLocation, 0, 200) : null,
            'start_at' => $start,
            'end_at' => $end,
            'all_day' => $this->eventAllDay,
            'color' => preg_match('/^#[0-9a-fA-F]{6}$/', $this->eventColor) ? $this->eventColor : '#0ea5e9',
        ];

        if ($this->editingId) {
            $event = CalendarEvent::find($this->editingId);
            if (! $event) {
                Notification::make()->title(__('admin.calendar.modal.deleted'))->danger()->send();
                $this->closeFormModal();
                return;
            }
            $event->update($payload);
            Notification::make()->title(__('admin.calendar.modal.updated'))->success()->send();
        } else {
            $event = CalendarEvent::create($payload);
            Notification::make()->title(__('admin.calendar.modal.created'))->success()->send();

            $discord = DiscordPayloads::newCalendarEvent($event);
            PostDiscordWebhook::dispatch($discord['content'], $discord['embed'], $discord['reference'])->afterResponse();
        }

        $this->closeFormModal();
    }

    public function deleteEvent(): void
    {
        if (! $this->editingId) return;
        $event = CalendarEvent::find($this->editingId);
        if (! $event) {
            $this->closeFormModal();
            return;
        }
        $event->delete();
        Notification::make()->title(__('admin.calendar.modal.deleted'))->warning()->send();
        $this->closeFormModal();
    }
}
