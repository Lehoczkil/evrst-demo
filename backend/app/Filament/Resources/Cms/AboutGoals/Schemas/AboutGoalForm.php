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
                    ->label(__('admin.common.title') . ' (EN)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 1, 'md' => 4]),
                TextInput::make('title_hu')
                    ->label(__('admin.common.title') . ' (HU)')
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 1, 'md' => 4]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->integer()
                    ->minValue(0)
                    ->step(1)
                    ->helperText(__('admin.common.sort_help'))
                    ->default(0)
                    ->columnSpan(['default' => 1, 'md' => 4]),
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
