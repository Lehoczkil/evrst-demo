<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Tasks\TaskResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\Task;
use App\Notifications\TaskStatusChanged;
use App\Support\DiscordPayloads;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class KanbanBoard extends Page
{
    protected static string $resource = TaskResource::class;

    protected string $view = 'filament.resources.tasks.pages.kanban-board';


    public ?int $filterAssigneeId = null;
    public ?int $filterSupervisorId = null;
    public ?string $filterStatus = null;
    public string $filterSearch = '';

    public function getTitle(): string
    {
        return __('admin.tasks.kanban');
    }

    /**
     * Header actions on the kanban page — currently just a quick-create
     * shortcut so admins don't have to bounce back to the list view to
     * add a new card.
     */
    protected function getHeaderActions(): array
    {
        $actions = [
            \Filament\Actions\Action::make('listView')
                ->label(__('admin.tasks.table_view'))
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(TaskResource::getUrl('index')),
        ];
        if (auth()->user()?->can(Perm::TASKS_CREATE)) {
            $actions[] = \Filament\Actions\Action::make('newTask')
                ->label(__('admin.tasks.new_task'))
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(TaskResource::getUrl('create'));
        }
        return $actions;
    }

    public function clearFilters(): void
    {
        $this->filterAssigneeId = null;
        $this->filterSupervisorId = null;
        $this->filterStatus = null;
        $this->filterSearch = '';
    }

    /** @return array<int, array{id: int, name: string}> */
    public function getUserOptions(): array
    {
        return \Illuminate\Support\Facades\Cache::remember(
            'options:assignees',
            300,
            fn () => \App\Models\User::orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])
                ->all(),
        );
    }

    /**
     * Eager-load everything the cards display, then group by status,
     * preserving the user-defined `position` ordering inside each column.
     *
     * @return array<int, array{key: string, label: string, accent: string, tasks: array<int, Task>}>
     */
    public function getColumns(): array
    {
        $query = Task::with(['assignees', 'supervisor'])
            ->withCount('children')
            ->orderBy('position')
            ->orderBy('id');

        if ($this->filterSupervisorId) {
            $query->where('supervisor_id', $this->filterSupervisorId);
        }
        if ($this->filterAssigneeId) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $this->filterAssigneeId));
        }
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }
        if ($this->filterSearch !== '') {
            $like = '%' . $this->filterSearch . '%';
            $query->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like));
        }

        $tasks = $query->get()->groupBy('status');

        $palette = [
            Task::STATUS_TODO        => '#94a3b8', // slate
            Task::STATUS_IN_PROGRESS => '#f59e0b', // amber
            Task::STATUS_TESTING     => '#0ea5e9', // sky
            Task::STATUS_DONE        => '#10b981', // emerald
        ];

        $cols = [];
        foreach (Task::statuses() as $status) {
            $cols[] = [
                'key' => $status,
                'label' => Task::statusLabel($status),
                'accent' => $palette[$status] ?? '#94a3b8',
                'tasks' => ($tasks->get($status, collect()))->all(),
            ];
        }
        return $cols;
    }

    public function canEditTasks(): bool
    {
        return auth()->user()?->can(Perm::TASKS_EDIT) ?? false;
    }

    /**
     * Drag-and-drop reorder is only safe when the board shows ALL tasks.
     * With a filter applied we'd rewrite `position` over a subset and
     * leave the hidden cards stranded with stale positions. The Blade
     * view consults this and disables Sortable on every column when true.
     */
    public function isFiltered(): bool
    {
        return $this->filterAssigneeId !== null
            || $this->filterSupervisorId !== null
            || $this->filterStatus !== null
            || $this->filterSearch !== '';
    }

    /**
     * Persist a board re-order from the SortableJS frontend. The payload
     * is shaped: { "TODO": [3, 1, 2], "IN_PROGRESS": [...], ... }.
     *
     * For each task whose status changes we send a TaskStatusChanged
     * notification to all watchers (excluding the actor). Pure re-orders
     * within the same column don't notify.
     *
     * @param  array<string, array<int, int|string>>  $payload
     */
    public function reorder(array $payload): void
    {
        if (! $this->canEditTasks()) {
            FilamentNotification::make()->title('Not allowed')->danger()->send();
            return;
        }

        if ($this->isFiltered()) {
            FilamentNotification::make()
                ->title('Drag disabled while filters are active')
                ->body('Clear filters to reorder cards.')
                ->warning()
                ->send();
            return;
        }

        $allowedStatuses = Task::statuses();
        $movedAcrossColumns = [];
        $rejected = [];

        DB::transaction(function () use ($payload, $allowedStatuses, &$movedAcrossColumns, &$rejected) {
            foreach ($payload as $status => $ids) {
                if (! in_array($status, $allowedStatuses, true)) continue;
                if (! is_array($ids)) continue;

                $position = 0;
                foreach ($ids as $rawId) {
                    $taskId = (int) $rawId;
                    if ($taskId <= 0) continue;
                    $task = Task::find($taskId);
                    if (! $task) continue;

                    $statusChanged = $task->status !== $status;
                    if ($statusChanged && ! $task->canTransitionTo(auth()->user(), $status)) {
                        // Snap the card back to its current column by
                        // skipping the status change but still updating
                        // its position within its existing column later.
                        $rejected[] = ['task' => $task, 'attempted' => $status];
                        continue;
                    }
                    if ($statusChanged) {
                        $movedAcrossColumns[] = [
                            'task' => $task,
                            'previous' => $task->status,
                            'next' => $status,
                        ];
                    }
                    $task->status = $status;
                    $task->position = $position++;
                    // Suppress both LogsActivity and the Task::saving guard
                    // here — a drag can touch dozens of rows, and we've
                    // already validated the transition above.
                    $task->skipStatusGuard = true;
                    $task->withoutActivityLog(fn () => $task->save());
                    $task->skipStatusGuard = false;
                }
            }
        });

        // One concise activity entry per task that actually changed column.
        foreach ($movedAcrossColumns as $entry) {
            $entry['task']->logActivity('updated', [
                'status' => ['from' => $entry['previous'], 'to' => $entry['next']],
            ]);
        }

        foreach ($movedAcrossColumns as $entry) {
            $task = $entry['task']->fresh(['assignees', 'supervisor']);
            $watchers = $task->watchers(auth()->id());
            if ($watchers->isNotEmpty()) {
                Notification::send($watchers, new TaskStatusChanged($task, $entry['previous'], $entry['next']));
                foreach ($watchers as $watcher) {
                    if (! DiscordPayloads::wantsDiscordPing($watcher)) continue;
                    $payload = DiscordPayloads::taskStatusChangedPing($task, $watcher, $entry['previous'], $entry['next']);
                    PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();
                }
            }
        }

        if (! empty($rejected)) {
            FilamentNotification::make()
                ->title(__('admin.tasks.transition_denied'))
                ->body(__('admin.tasks.transition_denied_body', ['count' => count($rejected)]))
                ->warning()
                ->send();
        }

        if (! empty($movedAcrossColumns)) {
            FilamentNotification::make()
                ->title(count($movedAcrossColumns) === 1 ? 'Task moved' : count($movedAcrossColumns) . ' tasks moved')
                ->success()
                ->send();
        }
    }
}
