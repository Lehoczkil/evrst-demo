<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class MyTasksWidget extends Widget
{
    protected string $view = 'filament.widgets.my-tasks';

    public function getHeading(): ?string { return __('admin.widgets.my_tasks'); }

    protected int|string|array $columnSpan = 1;

    /** @return Collection<int, Task> */
    public function getTasks(): Collection
    {
        $userId = auth()->id();
        if (! $userId) return collect();
        return Task::query()
            ->where(function ($q) use ($userId) {
                $q->where('supervisor_id', $userId)
                  ->orWhereHas('assignees', fn ($q2) => $q2->where('users.id', $userId));
            })
            ->whereIn('status', [Task::STATUS_TODO, Task::STATUS_IN_PROGRESS, Task::STATUS_TESTING])
            ->orderBy('due_date')
            ->limit(5)
            ->get();
    }

    public function urlFor(Task $task): string
    {
        return TaskResource::getUrl('edit', ['record' => $task->id]);
    }
}
