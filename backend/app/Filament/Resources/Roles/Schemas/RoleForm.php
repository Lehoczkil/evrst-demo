<?php

namespace App\Filament\Resources\Roles\Schemas;

use App\Models\Permission;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('key')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Slug used in code — not editable from the UI.'),
                    ])
                    ->columns(2),
                Section::make('Permissions')
                    ->description('Toggle the actions this role can perform across the admin.')
                    ->components([
                        CheckboxList::make('permissions')
                            ->label('Granted permissions')
                            ->relationship('permissions', 'label')
                            ->options(fn () => Permission::orderBy('id')->pluck('label', 'id')->all())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row'),
                    ]),
            ]);
    }
}
