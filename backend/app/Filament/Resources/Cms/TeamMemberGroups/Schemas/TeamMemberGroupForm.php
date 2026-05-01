<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Schemas;

use App\Models\TeamMemberGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TeamMemberGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name_en')
                    ->label(__('admin.common.name') . ' (EN)')
                    ->required()
                    ->maxLength(120)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (blank($get('slug')) && filled($state)) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('name_hu')
                    ->label(__('admin.common.name') . ' (HU)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(64)
                    ->alphaDash()
                    ->helperText('Stable code-side identifier; survives renames.')
                    ->unique(table: 'team_member_groups', column: 'slug', ignoreRecord: true)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('kind')
                    ->label('Kind')
                    ->options([
                        'leadership' => 'Leadership',
                        'department' => 'Department',
                        'squad' => 'Squad',
                    ])
                    ->default('department')
                    ->required()
                    ->columnSpan(['default' => 12, 'md' => 3]),
                Select::make('parent_id')
                    ->label('Parent group')
                    ->options(fn ($record) => TeamMemberGroup::query()
                        ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                        ->orderBy('position')
                        ->get()
                        ->mapWithKeys(fn ($g) => [$g->id => TeamMemberGroup::pickLocale($g->name) ?? $g->slug])
                        ->all())
                    ->searchable()
                    ->placeholder('—')
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Toggle::make('is_public')
                    ->label('Show on public team page')
                    ->default(true)
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
