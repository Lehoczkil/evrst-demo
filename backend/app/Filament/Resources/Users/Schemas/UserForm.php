<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Role;
use App\Support\OrgEmail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.users.login_email'))
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(180)
                    ->placeholder(fn () => OrgEmail::forName('Lehoczki László'))
                    ->helperText(__('admin.users.login_email_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('role_id')
                    ->label(__('admin.common.role'))
                    ->required()
                    ->options(fn () => Cache::remember(
                        'options:roles',
                        300,
                        fn () => Role::orderBy('name')->pluck('name', 'id')->all(),
                    ))
                    ->native(false)
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.user_role'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('password')
                    ->label(__('admin.users.password'))
                    ->password()
                    ->revealable()
                    ->minLength(8)
                    ->maxLength(128)
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Facades\Hash::make($state))
                    ->helperText(__('admin.users.password_help'))
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.user_password'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
            ])
            ->columns(12);
    }
}
