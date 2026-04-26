<?php

namespace App\Filament\Resources\Cms\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title_en')
                    ->label('Title (EN)')
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('title_hu')
                    ->label('Title (HU)')
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Select::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'PUBLISHED' => 'Published',
                    ])
                    ->default('DRAFT')
                    ->required()
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('start_at')
                    ->label('Starts')
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->required()
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('end_at')
                    ->label('Ends')
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->after('start_at')
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('content_en')
                    ->label('Content (EN)')
                    ->rows(5)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('content_hu')
                    ->label('Content (HU)')
                    ->rows(5)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('image')
                    ->image()
                    ->directory('events')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
