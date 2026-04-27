<?php

namespace App\Filament\Resources\Cms\Events\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                ImageColumn::make('image')
                    ->disk('public')
                    ->size(52)
                    ->label(''),
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(60),
                TextColumn::make('date')
                    ->label(__('admin.common.date'))
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.common.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'DRAFT' => __('admin.events.statuses.draft'),
                        'PUBLISHED' => __('admin.events.statuses.upcoming'),
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'DRAFT',
                        'success' => 'PUBLISHED',
                    ]),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.common.status'))
                    ->options(['DRAFT' => __('admin.events.statuses.draft'), 'PUBLISHED' => __('admin.events.statuses.upcoming')])
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) return;
                        $query->whereRaw("json_extract(payload, '$.status') = ?", [$value]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
