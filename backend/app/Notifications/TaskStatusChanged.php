<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $body = sprintf(
            '%s — %s → %s',
            $this->task->title,
            Task::statusLabel($this->previousStatus),
            Task::statusLabel($this->newStatus),
        );

        return FilamentNotification::make()
            ->title('Task status changed')
            ->body($body)
            ->icon('heroicon-o-arrow-path')
            ->iconColor('warning')
            ->actions([
                Action::make('view')
                    ->label('Open task')
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
