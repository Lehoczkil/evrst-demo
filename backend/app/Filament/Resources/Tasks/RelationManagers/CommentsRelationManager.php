<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Models\Task;
use App\Models\TaskComment;
use App\Notifications\TaskCommented;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Textarea::make('body')
                    ->label('Comment')
                    ->required()
                    ->rows(4)
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('body')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('author.name')
                    ->label('Author')
                    ->weight('semibold'),
                TextColumn::make('body')
                    ->wrap()
                    ->limit(180),
                TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add comment')
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    })
                    ->after(function (Model $record) {
                        /** @var TaskComment $record */
                        /** @var Task $task */
                        $task = $this->getOwnerRecord();
                        $watchers = $task->watchers(auth()->id());
                        if ($watchers->isNotEmpty()) {
                            Notification::send($watchers, new TaskCommented($task, $record));
                        }
                    })
                    ->visible(fn () => $this->canCommentOnOwner()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (TaskComment $r) => $this->canEditComment($r)),
                DeleteAction::make()
                    ->visible(fn (TaskComment $r) => $this->canEditComment($r)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->isAdmin() ?? false),
                ]),
            ]);
    }

    /**
     * Anyone tied to the task (assignee or supervisor) — plus admins —
     * may add a comment. This is intentionally lenient because Members
     * don't have any tasks.* permissions but they should still be able
     * to talk on tasks they're working on.
     */
    protected function canCommentOnOwner(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if ($u->isAdmin()) return true;
        /** @var Task $task */
        $task = $this->getOwnerRecord();
        $task->loadMissing(['assignees', 'supervisor']);
        return $task->supervisor_id === $u->id
            || $task->assignees->contains('id', $u->id);
    }

    protected function canEditComment(TaskComment $comment): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if ($u->isAdmin()) return true;
        /** @var Task $task */
        $task = $this->getOwnerRecord();
        // Author can edit own comment; supervisor can edit any comment on
        // the task (per the spec — "the user and the supervisor can create
        // and edit comments").
        return $comment->user_id === $u->id || $task->supervisor_id === $u->id;
    }
}
