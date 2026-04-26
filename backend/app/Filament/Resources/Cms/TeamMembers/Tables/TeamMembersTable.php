<?php

namespace App\Filament\Resources\Cms\TeamMembers\Tables;

use App\Models\Cms\TeamMemberGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('photo')
                    ->disk('public')
                    ->circular()
                    ->size(48)
                    ->label(''),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('degree')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('main_position_id')
                    ->label('Main position')
                    ->state(fn ($record) => $record->payload['main_position']['payload']['name'] ?? '—')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('position_ids')
                    ->label('Positions')
                    ->state(function ($record) {
                        $positions = $record->payload['positions'] ?? [];
                        if (! is_array($positions)) return [];
                        return array_values(array_filter(array_map(
                            fn ($p) => $p['payload']['name'] ?? null,
                            $positions,
                        )));
                    })
                    ->badge()
                    ->color('gray'),
                TextColumn::make('position')
                    ->label('Sort')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('main_position_id')
                    ->label('Main position')
                    ->options(fn () => TeamMemberGroup::all()->mapWithKeys(fn ($g) => [$g->id => $g->name ?? $g->id]))
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) return;
                        $query->whereRaw("json_extract(payload, '$.main_position.id') = ?", [$value]);
                    }),
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
