<?php

namespace App\Filament\Resources\Cms\Mentors\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MentorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->email()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('photo')
                    ->image()
                    ->imageEditor()
                    ->directory('mentors')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
