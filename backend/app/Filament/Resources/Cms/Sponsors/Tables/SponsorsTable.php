<?php

namespace App\Filament\Resources\Cms\Sponsors\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SponsorsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('logo')
                    ->disk('public')
                    ->size(48)
                    ->label(''),
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('year')
                    ->label(__('admin.common.year'))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('url')
                    ->label(__('admin.common.website'))
                    ->url(fn ($record) => $record->url, true)
                    ->limit(36)
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.empty.sponsors_h'))
            ->emptyStateDescription(__('admin.empty.sponsors_b'))
            ->emptyStateIcon('heroicon-o-heart');
    }
}
