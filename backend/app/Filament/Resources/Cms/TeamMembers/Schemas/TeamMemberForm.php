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
                    ->required()
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->email()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('degree_en')
                    ->label('Degree (EN)')
                    ->maxLength(120)
                    ->placeholder('e.g. BSc Mech. Eng.')
                    ->columnSpan(['default' => 12, 'md' => 3]),
                TextInput::make('degree_hu')
                    ->label('Degree (HU)')
                    ->maxLength(120)
                    ->placeholder('pl. BSc Gépészmérnök')
                    ->columnSpan(['default' => 12, 'md' => 3]),
                Select::make('user_id')
                    ->label('Linked admin account')
                    ->helperText('Set automatically when an application is accepted; clear here to unlink.')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                ...MemberPositionFields::components(),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                FileUpload::make('photo')
                    ->image()
                    ->imageEditor()
                    ->directory('team-members')
                    ->visibility('public')
                    ->disk('public')
                    ->columnSpanFull(),
            ])
            ->columns(12);
    }
}
