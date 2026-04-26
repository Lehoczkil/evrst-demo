<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                Select::make('status')
                    ->options(Task::statusLabels())
                    ->default(Task::STATUS_TODO)
                    ->required()
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('description')
                    ->rows(5)
                    ->maxLength(5000)
                    ->columnSpan(12),
                Select::make('supervisor_id')
                    ->label('Supervisor')
                    ->options(fn () => User::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('assignees')
                    ->label('Assignees')
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DatePicker::make('due_date')
                    ->displayFormat('d M Y')
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label('Sort order')
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ])
            ->columns(12);
    }
}
