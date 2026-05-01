<?php

namespace App\Filament\Resources\Items\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.items.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(),
                TextColumn::make('stocks_count')
                    ->label(__('admin.items.locations'))
                    ->counts('stocks')
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('total_quantity')
                    ->label(__('admin.items.total_quantity'))
                    ->state(fn ($record) => $record->totalQuantity())
                    ->numeric()
                    ->alignEnd()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.items.updated_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateHeading(__('admin.items.empty_heading'))
            ->emptyStateDescription(__('admin.items.empty_body'))
            ->emptyStateIcon('heroicon-o-cube');
    }
}
