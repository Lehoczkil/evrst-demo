<?php

namespace App\Filament\Resources\Resources\Tables;

use App\Models\Collection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ResourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager-load what the columns read — Filament does no
            // automatic eager loading, so without this the collection column
            // fire one query per row.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['collection']))
            ->defaultSort('position')
            ->columns([
                TextColumn::make('label')
                    ->label(__('admin.common.title'))
                    ->state(fn ($record) => $record->resourceLabel())
                    ->searchable(query: function ($query, string $search) {
                        $query->whereRaw("json_extract(payload, '$.name') like ?", ["%{$search}%"])
                            ->orWhereRaw("json_extract(payload, '$.title') like ?", ["%{$search}%"]);
                    })
                    ->limit(50)->toggleable(),
                TextColumn::make('collection.name')
                    ->label(__('admin.cms.collection'))
                    ->badge()
                    ->sortable()->toggleable(),
                TextColumn::make('position')
                    ->numeric()
                    ->sortable()->toggleable(),
                TextColumn::make('id')
                    ->label(__('admin.common.id'))
                    ->copyable()
                    ->copyMessage('UUID copied')
                    ->limit(8)
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.common.updated_at'))
                    ->since()
                    ->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('collection_id')
                    ->label(__('admin.cms.collection'))
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
