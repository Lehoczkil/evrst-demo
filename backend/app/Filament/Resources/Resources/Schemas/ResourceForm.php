<?php

namespace App\Filament\Resources\Resources\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ResourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('collection_id')
                    ->label(__('admin.resources.collection.s'))
                    ->relationship('collection', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('position')
                    ->label(__('admin.common.sort'))
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText(__('admin.help.fields.sponsor_position')),
                Textarea::make('payload')
                    ->label('Payload (JSON)')
                    ->rows(14)
                    ->columnSpanFull()
                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $state)
                    ->rules(['nullable', function () {
                        return function (string $attribute, $value, \Closure $fail) {
                            if ($value === null || $value === '') return;
                            json_decode($value, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                $fail('Payload must be valid JSON.');
                            }
                        };
                    }])
                    ->helperText('Stored as JSON. Edit as raw JSON for now; per-collection field editors can be added later.'),
            ]);
    }
}
