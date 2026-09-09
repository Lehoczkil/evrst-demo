<?php

namespace App\Filament\Resources\TeamMemberGroups\Tables;

use App\Models\TeamMemberGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamMemberGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Eager-load what the columns read — Filament does no
            // automatic eager loading, so without this the parent-group column
            // fire one query per row.
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['parent']))
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
                    ->label(__('admin.team.group_parent_col'))
                    ->state(fn ($record) => $record->parent ? (TeamMemberGroup::pickLocale($record->parent->name) ?? $record->parent->slug) : '—')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_public')
                    ->label(__('admin.team.group_public_col'))
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
