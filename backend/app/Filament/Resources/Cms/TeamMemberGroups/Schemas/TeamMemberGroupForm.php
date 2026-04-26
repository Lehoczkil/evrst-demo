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
                    ->label('Name (EN)')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('e.g. Propulsion')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('name_hu')
                    ->label('Name (HU)')
                    ->required()
                    ->maxLength(120)
                    ->placeholder('e.g. Hajtómű')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
