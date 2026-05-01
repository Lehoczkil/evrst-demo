<?php

namespace App\Filament\Resources\Items\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.items.section_what'))
                    ->components([
                        TextInput::make('name')
                            ->label(__('admin.items.name'))
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
