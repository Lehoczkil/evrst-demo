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
                Section::make(__('admin.roles.role'))
                    ->components([
                        TextInput::make('name')
                            ->label(__('admin.common.name'))
                            ->required()
                            ->maxLength(120),
                        TextInput::make('key')
                            ->label(__('admin.roles.key'))
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText(__('admin.roles.key_help')),
                    ])
                    ->columns(2),
                Section::make(__('admin.roles.permissions'))
                    ->description(__('admin.roles.permissions_help'))
                    ->components([
                        CheckboxList::make('permissions')
                            ->label(__('admin.roles.granted'))
                            ->relationship('permissions', 'label')
                            ->options(fn () => Permission::orderBy('id')->pluck('label', 'id')->all())
                            ->searchable()
                            ->bulkToggleable()
                            ->columns(2)
                            ->gridDirection('row')
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.role_permissions')),
                    ]),
            ]);
    }
}
