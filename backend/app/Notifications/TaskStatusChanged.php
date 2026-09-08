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
        $body = __('admin.mail.status_body', [
            'title' => $this->task->title,
            'from' => $this->statusLabel($this->previousStatus),
            'to' => $this->statusLabel($this->newStatus),
        ]);

        return FilamentNotification::make()
            ->title(__('admin.mail.status_title'))
            ->body($body)
            ->icon('heroicon-o-arrow-path')
            ->iconColor('warning')
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
            ->subject(__('admin.mail.status_subject', ['title' => $this->task->title]))
            ->greeting(__('admin.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('admin.mail.status_line', [
                'title' => $this->task->title,
                'from' => $this->statusLabel($this->previousStatus),
                'to' => $this->statusLabel($this->newStatus),
            ]))
            ->action(
                __('admin.mail.open_task'),
                TaskResource::getUrl('edit', ['record' => $this->task->id]),
            );
    }

    /**
     * Task::statusLabels() is hardcoded English (it feeds internal state,
     * not the UI). The panel's own translated map is keyed by the same
     * status constants, so prefer it and fall back to the raw key.
     * `admin.tasks.statuses` is an array, hence trans() + array lookup
     * rather than a dotted __() call.
     */
    private function statusLabel(string $status): string
    {
        $labels = trans('admin.tasks.statuses');

        return is_array($labels)
            ? ($labels[$status] ?? Task::statusLabel($status))
            : Task::statusLabel($status);
    }
}
