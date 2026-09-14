<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Support\DiscordDelivery;
use App\Support\DiscordPayloads;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Notification;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

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
            ->with('teamMember')
            ->get();
        if ($recipients->isEmpty()) return;

        Notification::send($recipients, new TaskAssigned($task));

        foreach ($recipients as $assignee) {
            DiscordDelivery::toRecipient($assignee, DiscordPayloads::taskAssignedPing($task, $assignee));
        }
    }
}
