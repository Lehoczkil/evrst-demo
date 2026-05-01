<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Tables;

use App\Models\TeamMemberGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TeamMemberGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->state(fn ($record) => TeamMemberGroup::pickLocale($record->name) ?? $record->slug)
                    ->searchable(query: fn ($query, $search) => $query
                        ->where('slug', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%")
                        ->orWhere('name->hu', 'like', "%{$search}%"))
                    ->weight('semibold')->toggleable(),
                TextColumn::make('slug')
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('kind')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->state(fn ($record) => $record->parent ? (TeamMemberGroup::pickLocale($record->parent->name) ?? $record->parent->slug) : '—')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()->toggleable(),
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
