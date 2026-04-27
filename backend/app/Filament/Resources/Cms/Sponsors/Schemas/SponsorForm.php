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
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('url')
                    ->label(__('admin.common.website'))
                    ->url()
                    ->placeholder('https://example.com')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('year')
                    ->label(__('admin.common.year'))
                    ->numeric()
                    ->placeholder('e.g. 2025')
                    ->columnSpan(['default' => 6, 'md' => 3]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 6, 'md' => 3]),
                Textarea::make('description_en')
                    ->label(__('admin.common.description') . ' (EN)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('description_hu')
                    ->label(__('admin.common.description') . ' (HU)')
                    ->rows(3)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('logo')
                    ->label(__('admin.sponsors.logo'))
                    ->image()
                    // iPhone Safari uploads as image/heic and Android sometimes as
                    // image/* with no extension; Filament's ->image() helper rejects
                    // both, so spell out the full mobile-friendly accept list.
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/svg+xml',
                        'image/webp',
                        'image/gif',
                        'image/heic',
                        'image/heif',
                    ])
                    ->maxSize(8192)
                    ->openable()
                    ->downloadable()
                    ->directory('sponsors')
                    ->visibility('public')
                    ->disk('public')
                    ->panelLayout('integrated')
                    ->helperText(__('admin.sponsors.logo_help'))
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
