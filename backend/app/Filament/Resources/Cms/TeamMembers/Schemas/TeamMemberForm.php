<?php

namespace App\Filament\Resources\Cms\TeamMembers\Schemas;

use App\Filament\Schemas\MemberPositionFields;
use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
                TextInput::make('email')
                    ->label(__('admin.common.email'))
                    ->email()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('private_email')
                    ->label(__('admin.team.private_email'))
                    ->email()
                    ->maxLength(180)
                    ->helperText(__('admin.team.private_email_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('discord_name')
                    ->label(__('admin.team.discord'))
                    ->maxLength(64)
                    ->placeholder('e.g. balint_klabacsek')
                    ->prefix('@')
                    ->helperText(__('admin.team.discord_help'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('user_id')
                    ->label(__('admin.team.linked_user'))
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('degree_en')
                    ->label(__('admin.team.degree') . ' (EN)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('degree_hu')
                    ->label(__('admin.team.degree') . ' (HU)')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 3]),
                ...MemberPositionFields::components(),
                TextInput::make('position')
                    ->label(__('admin.sponsors.sort_order'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('photo')
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
