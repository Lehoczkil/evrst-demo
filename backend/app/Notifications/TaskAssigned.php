<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskAssigned extends Notification
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title('Assigned to a task')
            ->body($this->task->title)
            ->icon('heroicon-o-clipboard-document-check')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label('Open task')
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
