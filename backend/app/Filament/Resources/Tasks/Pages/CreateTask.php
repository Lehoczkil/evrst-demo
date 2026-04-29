<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Jobs\PostDiscordWebhook;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Support\DiscordPayloads;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Notification;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var Task $task */
        $task = $this->record;
        $assigneeIds = $task->assignees()->pluck('users.id')->all();
        if (empty($assigneeIds)) return;

        $recipients = User::whereIn('id', $assigneeIds)
            ->where('id', '!=', auth()->id())
            ->get();
        if ($recipients->isEmpty()) return;

        Notification::send($recipients, new TaskAssigned($task));

        // Discord ping per newly-added assignee.
        foreach ($recipients as $assignee) {
            $payload = DiscordPayloads::newTaskAssigned($task, $assignee);
            PostDiscordWebhook::dispatch($payload['content'], $payload['embed'], $payload['reference'])->afterResponse();
        }
    }
}
