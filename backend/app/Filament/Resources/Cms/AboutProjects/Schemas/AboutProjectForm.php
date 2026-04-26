<?php

namespace App\Filament\Resources\Cms\AboutProjects\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AboutProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->helperText('Project name — not translated.')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('start_at')
                    ->label('Starts')
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DateTimePicker::make('end_at')
                    ->label('Ends')
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->after('start_at')
                    ->columnSpan(['default' => 12, 'md' => 6]),
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
