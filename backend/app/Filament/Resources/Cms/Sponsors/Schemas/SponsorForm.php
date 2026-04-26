<?php

namespace App\Filament\Resources\Cms\Sponsors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->helperText('Company / org name — not translated.')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('url')
                    ->label('Website')
                    ->url()
                    ->placeholder('https://example.com')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('year')
                    ->numeric()
                    ->placeholder('e.g. 2025')
                    ->columnSpan(['default' => 6, 'md' => 3]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 6, 'md' => 3]),
                Textarea::make('description_en')
                    ->label('Description (EN)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('description_hu')
                    ->label('Description (HU)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('logo')
                    ->image()
                    ->imageEditor()
                    ->directory('sponsors')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
