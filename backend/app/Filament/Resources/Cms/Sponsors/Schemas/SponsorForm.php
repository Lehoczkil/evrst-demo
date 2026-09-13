<?php

namespace App\Filament\Resources\Cms\Sponsors\Schemas;

use App\Filament\Support\Uploads;
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
                    ->integer()
                    ->minValue(0)
                    ->step(1)
                    ->helperText(__('admin.common.sort_help'))
                    ->default(0)
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.sponsor_position'))
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
                    // iPhone Safari uploads as image/heic and Android sometimes as
                    // image/* with no extension; Filament's ->image() helper rejects
                    // both, so spell out the full mobile-friendly accept list.
                    // SVG used to be on it — an SVG is a scriptable document, and
                    // a sponsor logo is served from our own origin.
                    ->acceptedFileTypes(Uploads::PHONE_IMAGE_TYPES)
                    ->getUploadedFileNameForStorageUsing(Uploads::storedName(...))
                    ->maxSize(8192)
                    ->openable()
                    ->downloadable()
                    ->directory('sponsors')
                    ->visibility('public')
                    ->disk('public')
                    ->panelLayout('integrated')
                    ->helperText(__('admin.sponsors.logo_help'))
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.sponsor_logo'))
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
