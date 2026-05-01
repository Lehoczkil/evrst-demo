<?php

namespace App\Filament\Resources\Cms\TeamMembers\Schemas;

use App\Filament\Schemas\MemberPositionFields;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeamMemberForm
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
                Select::make('user_id')
                    ->label(__('admin.team.linked_user'))
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.common.email'))
                    ->email()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email_private')
                    ->label(__('admin.team.private_email'))
                    ->email()
                    ->maxLength(180)
                    ->helperText(__('admin.team.private_email_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_nick')
                    ->label(__('admin.team.discord'))
                    ->maxLength(64)
                    ->placeholder('e.g. balint_klabacsek')
                    ->prefix('@')
                    ->helperText(__('admin.team.discord_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_username')
                    ->label('Discord username')
                    ->maxLength(64)
                    ->placeholder('e.g. balint_klabacsek')
                    ->helperText('Globally-unique Discord handle (the @name).')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_id')
                    ->label('Discord snowflake')
                    ->maxLength(32)
                    ->placeholder('123456789012345678')
                    ->rule('regex:/^\d{17,20}$/')
                    ->validationMessages(['regex' => 'Must be a 17–20 digit Discord snowflake.'])
                    ->helperText('Numeric user ID (Developer Mode → right-click → Copy User ID). Required for DM bot delivery and `<@id>` mentions.')
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('degree_en')
                    ->label(__('admin.team.degree') . ' (EN)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('degree_hu')
                    ->label(__('admin.team.degree') . ' (HU)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                DatePicker::make('joined_at')
                    ->label(__('admin.team.joined_at'))
                    ->native(false)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                DatePicker::make('left_at')
                    ->label(__('admin.team.left_at'))
                    ->native(false)
                    ->helperText(__('admin.team.left_at_help'))
                    ->columnSpan(['default' => 12, 'md' => 3]),
                ...MemberPositionFields::components(),
                Toggle::make('is_public')
                    ->label(__('admin.team.is_public'))
                    ->helperText(__('admin.team.is_public_help'))
                    ->default(true)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('photo_path')
                    ->label(__('admin.team.photo'))
                    ->image()
                    ->directory('team-members')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
