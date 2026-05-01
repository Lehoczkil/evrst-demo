<?php

namespace App\Filament\Resources\Tasks\Tables;

use App\Models\Task;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->badge()
                    ->formatStateUsing(fn (?string $s) => $s ? __('admin.tasks.priorities.' . $s) : '—')
                    ->color(fn (?string $s) => match ($s) {
                        Task::PRIORITY_URGENT => 'danger',
                        Task::PRIORITY_HIGH   => 'warning',
                        Task::PRIORITY_LOW    => 'gray',
                        default               => 'primary',
                    })->toggleable(),
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()->toggleable(),
                TextColumn::make('category')
                    ->label(__('admin.tasks.category'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $s) => $s ? __('admin.tasks.categories.' . $s) : '—')
                    ->toggleable(),
                TextColumn::make('parent.title')
                    ->label(__('admin.tasks.parent'))
                    ->limit(28)
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('children_count')
                    ->label(__('admin.tasks.subtasks'))
                    ->counts('children')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? (string) $state : '—')
                    ->toggleable(),
                // Plain text — the priority + status badges are the
                // only colour signals in the row, so people / role
                // columns stay monochrome to keep the row legible.
                TextColumn::make('supervisor.name')
                    ->label(__('admin.tasks.supervisor'))
                    ->color('gray')
                    ->placeholder('—')->toggleable(),
                TextColumn::make('assignees.name')
                    ->label(__('admin.tasks.assignees'))
                    ->color('gray')
                    ->listWithLineBreaks(false)
                    ->placeholder('—')->toggleable(),
                TextColumn::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->date('d M Y')
                    ->sortable()->toggleable(),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.tasks.statuses.' . $state))
                    ->color(fn ($state) => match ($state) {
                        Task::STATUS_TODO => 'gray',
                        Task::STATUS_IN_PROGRESS => 'warning',
                        Task::STATUS_TESTING => 'info',
                        Task::STATUS_DONE => 'success',
                        default => 'gray',
                    })->toggleable(),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options(fn () => collect(Task::statuses())->mapWithKeys(fn ($s) => [$s => __('admin.tasks.statuses.' . $s)])->all()),
                SelectFilter::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->options(fn () => collect(Task::priorities())
                        ->mapWithKeys(fn ($p) => [$p => __('admin.tasks.priorities.' . $p)])
                        ->all()),
                SelectFilter::make('category')
                    ->label(__('admin.tasks.category'))
                    ->options(fn () => collect(Task::categories())
                        ->mapWithKeys(fn ($c) => [$c => __('admin.tasks.categories.' . $c)])
                        ->all()),
                SelectFilter::make('supervisor_id')
                    ->label(__('admin.tasks.supervisor'))
                    ->options(fn () => \Illuminate\Support\Facades\Cache::remember(
                        'options:users',
                        300,
                        fn () => User::orderBy('name')->pluck('name', 'id')->all(),
                    )),
                SelectFilter::make('assignees')
                    ->relationship('assignees', 'name')
                    ->label(__('admin.tasks.assignees')),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.tasks_h'))
            ->emptyStateDescription(__('admin.empty.tasks_b'))
            ->emptyStateIcon('heroicon-o-clipboard-document-check');
    }
}
