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
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->weight('semibold')->toggleable(),
                TextColumn::make('key')
                    ->label(__('admin.roles.key'))
                    ->color('gray')
                    ->copyable()->toggleable(),
                TextColumn::make('permissions_count')
                    ->label(__('admin.roles.permissions'))
                    ->badge()
                    ->counts('permissions')
                    ->color('primary')->toggleable(),
                TextColumn::make('users_count')
                    ->label(__('admin.resources.user.p'))
                    ->badge()
                    ->counts('users')
                    ->color('gray')->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
