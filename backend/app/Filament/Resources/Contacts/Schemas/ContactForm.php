<?php

namespace App\Filament\Resources\Contacts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContactForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.contacts.section_who'))
                    ->description(__('admin.contacts.section_who_help'))
                    ->components([
                        TextInput::make('name')
                            ->label(__('admin.contacts.name'))
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(['default' => 12, 'md' => 8]),
                        Select::make('contact_group_id')
                            ->label(__('admin.contacts.group'))
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label(__('admin.contacts.group_name'))
                                    ->required()
                                    ->maxLength(120)
                                    ->unique(table: 'contact_groups', column: 'name'),
                                Textarea::make('description')
                                    ->label(__('admin.contacts.group_description'))
                                    ->rows(2)
                                    ->maxLength(1000),
                            ])
                            ->columnSpan(['default' => 12, 'md' => 4]),
                        TextInput::make('email')
                            ->label(__('admin.contacts.email'))
                            ->email()
                            ->maxLength(180)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('phone')
                            ->label(__('admin.contacts.phone'))
                            ->tel()
                            ->maxLength(32)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                    ])
                    ->columns(12),

                Section::make(__('admin.contacts.section_more'))
                    ->components([
                        Textarea::make('notes')
                            ->label(__('admin.contacts.notes'))
                            ->helperText(__('admin.contacts.notes_help'))
                            ->rows(4)
                            ->maxLength(5000)
                            ->columnSpanFull(),
                        TextInput::make('position')
                            ->label(__('admin.contacts.position'))
                            ->helperText(__('admin.contacts.position_help'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(['default' => 12, 'md' => 4]),
                    ])
                    ->columns(12),
            ]);
    }
}
