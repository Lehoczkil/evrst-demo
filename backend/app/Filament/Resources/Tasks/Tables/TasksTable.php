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
                ->label('Status')
                ->getTitleFromRecordUsing(fn (Task $r) => Task::statusLabel($r->status)))
            ->groupingSettingsHidden()
            ->defaultSort('position')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('supervisor.name')
                    ->label('Supervisor')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('assignees.name')
                    ->label('Assignees')
                    ->badge()
                    ->color('primary')
                    ->listWithLineBreaks(false),
                TextColumn::make('due_date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Task::statusLabel($state))
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
                    ->options(Task::statusLabels()),
                SelectFilter::make('supervisor_id')
                    ->label('Supervisor')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all()),
                SelectFilter::make('assignees')
                    ->relationship('assignees', 'name')
                    ->label('Assignee'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
