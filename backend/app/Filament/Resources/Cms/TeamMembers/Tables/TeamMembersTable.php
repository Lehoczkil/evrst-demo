<?php

namespace App\Filament\Resources\Cms\TeamMembers\Tables;

use App\Models\TeamMemberGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TeamMembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['groups']))
            ->defaultSort('position')
            ->columns([
                ImageColumn::make('photo_path')
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
                TextColumn::make('discord_nick')
                    ->label(__('admin.team.discord'))
                    ->searchable()
                    ->prefix('@')
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('discord_username')
                    ->label('Discord @')
                    ->searchable()
                    ->prefix('@')
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('discord_id')
                    ->label('Snowflake')
                    ->searchable()
                    ->copyable()
                    ->color('gray')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('degree')
                    ->label(__('admin.team.degree'))
                    ->state(fn ($record) => TeamMemberGroup::pickLocale($record->degree) ?? '—')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('primary_group')
                    ->label(__('admin.team.main_position'))
                    ->state(function ($record) {
                        $primary = $record->groups->firstWhere('pivot.is_primary', true);
                        return $primary ? (TeamMemberGroup::pickLocale($primary->name) ?? '—') : '—';
                    })
                    ->badge()
                    ->color('primary')->toggleable(),
                TextColumn::make('groups')
                    ->label(__('admin.team.positions'))
                    ->state(fn ($record) => $record->groups
                        ->map(fn ($g) => TeamMemberGroup::pickLocale($g->name))
                        ->filter()
                        ->values()
                        ->all())
                    ->badge()
                    ->color('gray')->toggleable(),
                IconColumn::make('is_public')
                    ->label(__('admin.team.is_public'))
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('left_at')
                    ->label(__('admin.team.left_at'))
                    ->date('d M Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('position')
                    ->label(__('admin.common.sort'))
                    ->numeric()
                    ->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('primary_group')
                    ->label(__('admin.team.main_position'))
                    ->options(fn () => TeamMemberGroup::all()
                        ->mapWithKeys(fn ($g) => [$g->id => TeamMemberGroup::pickLocale($g->name) ?? $g->slug]))
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;
                        if (! $value) return;
                        $query->whereHas('groups', fn ($q) => $q
                            ->where('team_member_groups.id', $value)
                            ->where('team_member_team_member_group.is_primary', true));
                    }),
                TernaryFilter::make('left_at')
                    ->label(__('admin.team.alumni_filter'))
                    ->placeholder(__('admin.team.alumni_filter_all'))
                    ->trueLabel(__('admin.team.alumni_filter_alumni'))
                    ->falseLabel(__('admin.team.alumni_filter_active'))
                    ->queries(
                        true: fn ($q) => $q->whereNotNull('left_at'),
                        false: fn ($q) => $q->whereNull('left_at'),
                    ),
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
