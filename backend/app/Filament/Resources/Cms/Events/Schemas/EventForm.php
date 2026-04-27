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
                    ->label(__('admin.common.title') . ' (EN)')
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('title_hu')
                    ->label(__('admin.common.title') . ' (HU)')
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Select::make('status')
                    ->label(__('admin.common.status'))
                    ->options([
                        'DRAFT' => __('admin.events.statuses.draft'),
                        'PUBLISHED' => __('admin.events.statuses.upcoming'),
                    ])
                    ->default('DRAFT')
                    ->required()
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.event_status'))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('start_at')
                    ->label(__('admin.events.start_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->required()
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.event_date_range'))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                DateTimePicker::make('end_at')
                    ->label(__('admin.events.end_at'))
                    ->seconds(false)
                    ->displayFormat('d M Y H:i')
                    ->after('start_at')
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('content_en')
                    ->label(__('admin.events.content') . ' (EN)')
                    ->rows(5)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('content_hu')
                    ->label(__('admin.events.content') . ' (HU)')
                    ->rows(5)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('image')
                    ->label(__('admin.events.image'))
                    ->image()
                    ->directory('events')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
