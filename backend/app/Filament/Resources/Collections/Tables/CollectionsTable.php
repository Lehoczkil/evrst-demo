<?php

namespace App\Filament\Resources\Collections\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CollectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()->toggleable(),
                TextColumn::make('slug')
                    ->badge()
                    ->searchable()->toggleable(),
                TextColumn::make('resources_count')
                    ->label('Resources')
                    ->counts('resources')
                    ->numeric()->toggleable(),
                TextColumn::make('description')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->copyMessage('UUID copied')
                    ->limit(8)
                    ->toggleable(isToggledHiddenByDefault: true),
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
