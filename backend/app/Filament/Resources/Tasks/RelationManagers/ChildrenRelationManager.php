<?php

namespace App\Filament\Resources\Tasks\RelationManagers;

use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

/**
 * Subtasks attached to the current task. The form mirrors the main
 * TaskForm but skips the parent-picker (the parent is implicit). The
 * table shows status / priority / due-date / assignees so the parent
 * page tells you at a glance what's blocking it from reaching DONE.
 */
class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tasks.subtasks');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('admin.common.title'))
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                Select::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->options(fn () => collect(Task::priorities())
                        ->mapWithKeys(fn ($p) => [$p => __('admin.tasks.priorities.' . $p)])
                        ->all())
                    ->default(Task::PRIORITY_NORMAL)
                    ->required()
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('description')
                    ->label(__('admin.common.description'))
                    ->required()
                    ->minLength(10)
                    ->maxLength(5000)
                    ->rows(4)
                    ->columnSpan(12),
                Select::make('supervisor_id')
                    ->label(__('admin.tasks.supervisor'))
                    ->required()
                    ->options(fn () => Cache::remember(
                        'options:users', 300,
                        fn () => User::orderBy('name')->pluck('name', 'id')->all(),
                    ))
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('assignees')
                    ->label(__('admin.tasks.assignees'))
                    ->required()
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->minItems(1)
                    ->preload()
                    ->searchable()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DatePicker::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->required()
                    ->displayFormat('d M Y')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('category')
                    ->label(__('admin.tasks.category'))
                    ->options(fn () => collect(Task::categories())
                        ->mapWithKeys(fn ($c) => [$c => __('admin.tasks.categories.' . $c)])
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->placeholder(__('admin.tasks.no_category'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->weight('semibold')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $s) => $s ? __('admin.tasks.priorities.' . $s) : '—')
                    ->color(fn (?string $s) => match ($s) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH   => 'warning',
                        Task::PRIORITY_LOW    => 'gray',
                        default               => 'primary',
                    }),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($s) => __('admin.tasks.statuses.' . $s))
                    ->color(fn ($s) => match ($s) {
                        Task::STATUS_TODO => 'gray',
                        Task::STATUS_IN_PROGRESS => 'warning',
                        Task::STATUS_TESTING => 'info',
                        Task::STATUS_DONE => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('assignees.name')
                    ->label(__('admin.tasks.assignees'))
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks(false),
                TextColumn::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->options(fn () => collect(Task::priorities())
                        ->mapWithKeys(fn ($p) => [$p => __('admin.tasks.priorities.' . $p)])
                        ->all()),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.tasks.add_subtask'))
                    ->mutateDataUsing(function (array $data): array {
                        $data['created_by'] = auth()->id();
                        $data['status'] = Task::STATUS_TODO;
                        return $data;
                    }),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.tasks.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Task $r) => TaskResource::getUrl('edit', ['record' => $r->id])),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.tasks.no_subtasks'))
            ->emptyStateDescription(__('admin.tasks.add_subtask_hint'))
            ->emptyStateIcon('heroicon-o-list-bullet');
    }
}
