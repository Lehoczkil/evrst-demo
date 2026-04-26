<?php

namespace App\Filament\Resources\Cms\AboutGoals\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AboutGoalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_en')
                    ->label('Title (EN)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('title_hu')
                    ->label('Title (HU)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('description_en')
                    ->label('Description (EN)')
                    ->rows(4)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('description_hu')
                    ->label('Description (HU)')
                    ->rows(4)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
