<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Task $task,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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

    public function toMail(object $notifiable): MailMessage
    {
        $prev = Task::statusLabel($this->previousStatus);
        $next = Task::statusLabel($this->newStatus);

        return (new MailMessage())
            ->subject('[EVRST] Task status: ' . $this->task->title)
            ->greeting('Hi ' . ($notifiable->name ?? 'there') . ',')
            ->line('Status of "' . $this->task->title . '" changed: ' . $prev . ' → ' . $next . '.')
            ->action('Open task', TaskResource::getUrl('edit', ['record' => $this->task->id]));
    }
}
