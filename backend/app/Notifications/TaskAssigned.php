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

class TaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title(__('admin.mail.assigned_title'))
            ->body($this->task->title)
            ->icon('heroicon-o-clipboard-document-check')
            ->iconColor('primary')
            ->actions([
                Action::make('view')
                    ->label(__('admin.mail.open_task'))
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('admin.mail.assigned_subject', ['title' => $this->task->title]))
            ->greeting(__('admin.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('admin.mail.assigned_line', ['title' => $this->task->title]))
            ->action(
                __('admin.mail.open_task'),
                TaskResource::getUrl('edit', ['record' => $this->task->id]),
            );
    }
}
