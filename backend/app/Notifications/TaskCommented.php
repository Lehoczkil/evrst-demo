<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\TaskComment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommented extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Task $task, public TaskComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        $author = $this->comment->author?->name ?? __('admin.mail.comment_someone');
        $snippet = Str::limit($this->comment->body, 120);

        return FilamentNotification::make()
            ->title(__('admin.mail.comment_title', ['title' => $this->task->title]))
            ->body($author . ': ' . $snippet)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('info')
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
        $author = $this->comment->author?->name ?? __('admin.mail.comment_someone');
        $snippet = Str::limit((string) $this->comment->body, 200);

        return (new MailMessage())
            ->subject(__('admin.mail.comment_subject', ['title' => $this->task->title]))
            ->greeting(__('admin.mail.greeting', ['name' => $notifiable->name ?? '']))
            ->line(__('admin.mail.comment_line', [
                'author' => $author,
                'title' => $this->task->title,
            ]))
            ->line($snippet)
            ->action(
                __('admin.mail.open_task'),
                TaskResource::getUrl('edit', ['record' => $this->task->id]),
            );
    }
}
