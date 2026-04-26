<?php

namespace App\Filament\Resources\Roles\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('key')
                    ->color('gray')
                    ->copyable(),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->badge()
                    ->counts('permissions')
                    ->color('primary'),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->badge()
                    ->counts('users')
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
