<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskStatusChanged;
use App\Support\DiscordPayloads;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Notification;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    /** Render the form across the full container width — the schema's
     *  12-column grid uses every cell, so a narrower card was leaving
     *  half the page empty. */
    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    /** @var array<int, int> */
    protected array $assigneeIdsBeforeSave = [];

    protected ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        /** @var Task $task */
        $task = $this->record->fresh(['assignees']);
        $this->assigneeIdsBeforeSave = $task?->assignees->pluck('id')->all() ?? [];
        $this->statusBeforeSave = $task?->status;
    }

    protected function afterSave(): void
    {
        /** @var Task $task */
        $task = $this->record->fresh(['assignees', 'supervisor']);

        // Newly added assignees → TaskAssigned notification.
        $afterIds = $task->assignees->pluck('id')->all();
        $newlyAdded = array_diff($afterIds, $this->assigneeIdsBeforeSave);
        if (! empty($newlyAdded)) {
            $recipients = User::whereIn('id', $newlyAdded)
                ->where('id', '!=', auth()->id())
                ->get();
            if ($recipients->isNotEmpty()) {
                Notification::send($recipients, new TaskAssigned($task));
                foreach ($recipients as $assignee) {
                    $payload = DiscordPayloads::newTaskAssigned($task, $assignee);
                    PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();
                }
            }
        }

        // Status change → notify all watchers (except the actor).
        if ($this->statusBeforeSave !== null && $task->status !== $this->statusBeforeSave) {
            $watchers = $task->watchers(auth()->id());
            if ($watchers->isNotEmpty()) {
                Notification::send($watchers, new TaskStatusChanged(
                    $task,
                    $this->statusBeforeSave,
                    $task->status,
                ));
            }
        }
    }
}
