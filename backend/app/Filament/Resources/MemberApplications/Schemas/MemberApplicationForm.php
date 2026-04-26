<?php

namespace App\Filament\Resources\MemberApplications\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MemberApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('university')
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('faculty')
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('education')
                    ->options(['BSc' => 'BSc', 'MSc' => 'MSc', 'PhD' => 'PhD'])
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('hours')
                    ->label('Hours / week')
                    ->maxLength(64)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('department')
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TagsInput::make('languages')
                    ->columnSpan(12),
                Textarea::make('why')
                    ->label('Why join?')
                    ->rows(3)
                    ->columnSpan(12),
                Textarea::make('tasks')
                    ->rows(3)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('skills')
                    ->rows(3)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'ACCEPTED' => 'Accepted',
                        'REJECTED' => 'Rejected',
                    ])
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ])
            ->columns(12);
    }
}
