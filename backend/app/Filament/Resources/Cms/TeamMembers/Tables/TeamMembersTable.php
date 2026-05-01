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
                    ->label('')->toggleable(),
                TextColumn::make('name')
                    ->label(__('admin.common.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')->toggleable(),
                TextColumn::make('email')
                    ->label(__('admin.common.email'))
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('discord_name')
                    ->label(__('admin.team.discord'))
                    ->state(fn ($record) => $record->payload['discord'] ?? null)
                    ->prefix('@')
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('degree')
                    ->label(__('admin.team.degree'))
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('main_position_id')
                    ->label(__('admin.team.main_position'))
                    ->state(fn ($record) => \App\Models\Cms\CollectionResource::pickLocale(
                        $record->payload['main_position']['payload']['name'] ?? null
                    ) ?? '—')
                    ->badge()
                    ->color('primary')->toggleable(),
                TextColumn::make('position_ids')
                    ->label(__('admin.team.positions'))
                    ->state(function ($record) {
                        $positions = $record->payload['positions'] ?? [];
                        if (! is_array($positions)) return [];
                        return array_values(array_filter(array_map(
                            fn ($p) => \App\Models\Cms\CollectionResource::pickLocale($p['payload']['name'] ?? null),
                            $positions,
                        )));
                    })
                    ->badge()
                    ->color('gray')->toggleable(),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('main_position_id')
                    ->label(__('admin.team.main_position'))
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
            ])
            ->emptyStateHeading(__('admin.empty.team_members_h'))
            ->emptyStateDescription(__('admin.empty.team_members_b'))
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
