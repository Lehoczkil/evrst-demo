<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

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
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('name_hu')
                    ->label(__('admin.common.name') . ' (HU)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
