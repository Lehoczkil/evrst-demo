<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class TaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label(__('admin.common.title'))
                    ->required()
                    ->maxLength(180)
                    ->columnSpan(['default' => 12, 'md' => 8]),
                Select::make('status')
                    ->label(__('admin.common.status'))
                    ->options(fn () => collect(Task::statuses())->mapWithKeys(fn ($s) => [$s => __('admin.tasks.statuses.' . $s)])->all())
                    ->default(Task::STATUS_TODO)
                    ->required()
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('description')
                    ->label(__('admin.common.description'))
                    ->rows(5)
                    ->maxLength(5000)
                    ->columnSpan(12),
                Select::make('supervisor_id')
                    ->label(__('admin.tasks.supervisor'))
                    ->options(fn () => Cache::remember(
                        'options:users',
                        300,
                        fn () => User::orderBy('name')->pluck('name', 'id')->all(),
                    ))
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('assignees')
                    ->label(__('admin.tasks.assignees'))
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->preload()
                    ->searchable()
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DatePicker::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->displayFormat('d M Y')
                    ->columnSpan(['default' => 12, 'md' => 4]),
                TextInput::make('position')
                    ->label(__('admin.tasks.position'))
                    ->numeric()
                    ->default(0)
                    ->columnSpan(['default' => 12, 'md' => 4]),
            ])
            ->columns(12);
    }
}
