<?php

namespace App\Notifications;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\TaskComment;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommented extends Notification
{
    use Queueable;

    public function __construct(public Task $task, public TaskComment $comment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $author = $this->comment->author?->name ?? 'Someone';
        $snippet = Str::limit($this->comment->body, 120);

        return FilamentNotification::make()
            ->title('New comment on ' . $this->task->title)
            ->body($author . ': ' . $snippet)
            ->icon('heroicon-o-chat-bubble-left-right')
            ->iconColor('info')
            ->actions([
                Action::make('view')
                    ->label('Open task')
                    ->url(TaskResource::getUrl('edit', ['record' => $this->task->id]))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
