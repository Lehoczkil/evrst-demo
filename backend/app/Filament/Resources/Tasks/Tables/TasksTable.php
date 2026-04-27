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
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultGroup(Group::make('status')
                ->label(__('admin.common.status'))
                ->getTitleFromRecordUsing(fn (Task $r) => __('admin.tasks.statuses.' . $r->status)))
            ->groupingSettingsHidden()
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('supervisor.name')
                    ->label(__('admin.tasks.supervisor'))
                    ->badge()
                    ->color('warning'),
                TextColumn::make('assignees.name')
                    ->label(__('admin.tasks.assignees'))
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks(false),
                TextColumn::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->date('d M Y')
                    ->sortable(),
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
                    }),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options(fn () => collect(Task::statuses())->mapWithKeys(fn ($s) => [$s => __('admin.tasks.statuses.' . $s)])->all()),
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
