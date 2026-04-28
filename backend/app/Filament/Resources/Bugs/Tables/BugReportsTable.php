<?php

namespace App\Filament\Resources\Bugs\Tables;

use App\Auth\Perm;
use App\Models\BugReport;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BugReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(60)
                    ->wrap(),
                TextColumn::make('severity')
                    ->label(__('admin.bugs.severity'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __('admin.bugs.severities.' . $state))
                    ->color(fn (string $state) => match ($state) {
                        BugReport::SEVERITY_CRITICAL => 'danger',
                        BugReport::SEVERITY_HIGH     => 'warning',
                        BugReport::SEVERITY_MEDIUM   => 'info',
                        default                       => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.bugs.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => __('admin.bugs.statuses.' . $state))
                    ->color(fn (string $state) => match ($state) {
                        BugReport::STATUS_OPEN        => 'warning',
                        BugReport::STATUS_TRIAGING    => 'info',
                        BugReport::STATUS_IN_PROGRESS => 'primary',
                        BugReport::STATUS_RESOLVED    => 'success',
                        BugReport::STATUS_CLOSED     ,
                        BugReport::STATUS_WONT_FIX    => 'gray',
                        default                       => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('reporter.name')
                    ->label(__('admin.bugs.reporter'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('assignee.name')
                    ->label(__('admin.bugs.assignee'))
                    ->searchable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('page_url')
                    ->label(__('admin.bugs.page_url'))
                    ->limit(40)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('admin.bugs.reported_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label(__('admin.bugs.resolved_at'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.bugs.status'))
                    ->options(collect(BugReport::statuses())->mapWithKeys(
                        fn ($s) => [$s => __('admin.bugs.statuses.' . $s)],
                    )->all())
                    ->default(null),
                SelectFilter::make('severity')
                    ->label(__('admin.bugs.severity'))
                    ->options(collect(BugReport::severities())->mapWithKeys(
                        fn ($s) => [$s => __('admin.bugs.severities.' . $s)],
                    )->all()),
                SelectFilter::make('reporter_id')
                    ->label(__('admin.bugs.reporter'))
                    ->relationship('reporter', 'name'),
                SelectFilter::make('assignee_id')
                    ->label(__('admin.bugs.assignee'))
                    ->relationship('assignee', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn () => auth()->user()?->can(Perm::BUGS_DELETE) ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.bugs.empty_heading'))
            ->emptyStateDescription(__('admin.bugs.empty_body'))
            ->emptyStateIcon('heroicon-o-bug-ant');
    }
}
