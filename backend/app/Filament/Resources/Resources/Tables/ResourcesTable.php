<?php

namespace App\Filament\Resources\Resources\Tables;

use App\Models\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ResourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('label')
                    ->label('Title')
                    ->state(fn ($record) => $record->resourceLabel())
                    ->searchable(query: function ($query, string $search) {
                        $query->whereRaw("json_extract(payload, '$.name') like ?", ["%{$search}%"])
                            ->orWhereRaw("json_extract(payload, '$.title') like ?", ["%{$search}%"]);
                    })
                    ->limit(50),
                TextColumn::make('collection.name')
                    ->label('Collection')
                    ->badge()
                    ->sortable(),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id')
                    ->label('ID')
                    ->copyable()
                    ->copyMessage('UUID copied')
                    ->limit(8)
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection_id')
                    ->label('Collection')
                    ->options(fn () => Collection::orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
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
