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
                    ->label(__('admin.common.name'))
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('email')
                    ->label(__('admin.common.email'))
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('university')
                    ->label(__('admin.applications.university'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                TextInput::make('faculty')
                    ->label(__('admin.applications.faculty'))
                    ->maxLength(255)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('education')
                    ->label(__('admin.applications.education'))
                    ->options(['BSc' => 'BSc', 'MSc' => 'MSc', 'PhD' => 'PhD'])
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('hours')
                    ->label(__('admin.applications.hours'))
                    ->maxLength(64)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('department')
                    ->label(__('admin.applications.department'))
                    ->maxLength(120)
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TagsInput::make('languages')
                    ->label(__('admin.applications.languages'))
                    ->columnSpan(12),
                Textarea::make('why')
                    ->label(__('admin.applications.why'))
                    ->rows(3)
                    ->columnSpan(12),
                Textarea::make('tasks')
                    ->label(__('admin.applications.tasks'))
                    ->rows(3)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Textarea::make('skills')
                    ->label(__('admin.applications.skills'))
                    ->rows(3)
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('status')
                    ->label(__('admin.common.status'))
                    ->options([
                        'PENDING' => __('admin.applications.statuses.PENDING'),
                        'ACCEPTED' => __('admin.applications.statuses.ACCEPTED'),
                        'REJECTED' => __('admin.applications.statuses.REJECTED'),
                    ])
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ])
            ->columns(12);
    }
}
