<?php

namespace App\Filament\Resources\Cms\AboutProjects\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
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
                    ->columnSpan(['default' => 1, 'md' => 8]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->integer()
                    ->minValue(0)
                    ->step(1)
                    ->helperText(__('admin.common.sort_help'))
                    ->default(0)
                    ->columnSpan(['default' => 1, 'md' => 4]),
                DateTimePicker::make('start_at')
                    ->label(__('admin.events.start_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->columnSpan(['default' => 1, 'md' => 6]),
                DateTimePicker::make('end_at')
                    ->label(__('admin.events.end_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->after('start_at')
                    ->columnSpan(['default' => 1, 'md' => 6]),
                /*
                  Per-vehicle facts the SPA used to keep in its message
                  files and match to a project by its POSITION in the
                  collection — so reordering the list here silently gave a
                  rocket someone else's altitude. They belong on the row.
                */
                Select::make('state')
                    ->label(__('admin.cms.project_state'))
                    ->options([
                        'flown' => __('admin.cms.project_states.flown'),
                        'building' => __('admin.cms.project_states.building'),
                        'design' => __('admin.cms.project_states.design'),
                    ])
                    ->native(false)
                    ->helperText(__('admin.cms.project_state_help'))
                    ->columnSpan(['default' => 1, 'md' => 4]),
                TextInput::make('years')
                    ->label(__('admin.cms.project_years'))
                    ->maxLength(40)
                    ->placeholder('2024 — 2025')
                    ->helperText(__('admin.cms.project_years_help'))
                    ->columnSpan(['default' => 1, 'md' => 4]),
                TextInput::make('apogee_en')
                    ->label(__('admin.cms.project_apogee') . ' (EN)')
                    ->maxLength(80)
                    ->placeholder('Apogee reached · 640 m')
                    ->helperText(__('admin.cms.project_apogee_help'))
                    ->columnSpan(['default' => 1, 'md' => 6]),
                TextInput::make('apogee_hu')
                    ->label(__('admin.cms.project_apogee') . ' (HU)')
                    ->maxLength(80)
                    ->placeholder('Elért csúcsmagasság · 640 m')
                    ->columnSpan(['default' => 1, 'md' => 6]),
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
                    ->columnSpan(['default' => 1, 'md' => 6]),
                Textarea::make('description_hu')
                    ->label(__('admin.common.description') . ' (HU)')
                    ->rows(4)
                    ->maxLength(500)
                    ->columnSpan(['default' => 1, 'md' => 6]),
            ])
            ->columns(['default' => 1, 'md' => 12]);
    }
}
