<?php

namespace App\Filament\Resources\ContactGroups\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContactGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.contacts.group_name'))
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label(__('admin.contacts.group_description'))
                    ->helperText(__('admin.contacts.group_description_help'))
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                TextInput::make('position')
                    ->label(__('admin.contacts.position'))
                    ->helperText(__('admin.contacts.position_help'))
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ])
            ->columns(12);
    }
}
