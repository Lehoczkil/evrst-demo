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
                    ->label(__('admin.common.title'))
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('start_at')
                    ->label(__('admin.events.start_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DateTimePicker::make('end_at')
                    ->label(__('admin.events.end_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->after('start_at')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_webhook_url')
                    ->label(__('admin.cms.project_webhook'))
                    ->url()
                    ->maxLength(255)
                    ->helperText(__('admin.cms.project_webhook_help'))
                    ->placeholder('https://discord.com/api/webhooks/…')
                    ->columnSpanFull(),
                Textarea::make('description_en')
                    ->label(__('admin.common.description') . ' (EN)')
                    ->rows(4)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('description_hu')
                    ->label(__('admin.common.description') . ' (HU)')
                    ->rows(4)
                    ->maxLength(500)
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
