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
                // Status select narrows to the moves $user is allowed to
                // make from the current row's status. On create the task
                // always starts as TODO, so the field is hidden then.
                Select::make('status')
                    ->label(__('admin.common.status'))
                    ->options(function ($record) {
                        if (! $record) return [Task::STATUS_TODO => __('admin.tasks.statuses.' . Task::STATUS_TODO)];
                        $allowed = $record->allowedTransitionsFor(auth()->user());
                        return collect($allowed)
                            ->mapWithKeys(fn ($s) => [$s => __('admin.tasks.statuses.' . $s)])
                            ->all();
                    })
                    ->helperText(fn ($record) => $record && ! $record->hasProof()
                        ? __('admin.tasks.proof_required_for_done')
                        : null)
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_status'))
                    ->default(Task::STATUS_TODO)
                    ->required()
                    ->disabled(fn ($record) => $record !== null
                        && count($record->allowedTransitionsFor(auth()->user())) <= 1)
                    ->dehydrated()
                    ->visibleOn('edit')
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Textarea::make('description')
                    ->label(__('admin.common.description'))
                    ->required()
                    ->rows(5)
                    ->minLength(10)
                    ->maxLength(5000)
                    ->helperText(__('admin.tasks.description_help'))
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_description'))
                    ->columnSpan(12),
                Select::make('supervisor_id')
                    ->label(__('admin.tasks.supervisor'))
                    ->required()
                    ->options(fn () => Cache::remember(
                        'options:users',
                        300,
                        fn () => User::orderBy('name')->pluck('name', 'id')->all(),
                    ))
                    ->searchable()
                    ->preload()
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_supervisor'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                Select::make('assignees')
                    ->label(__('admin.tasks.assignees'))
                    ->required()
                    ->multiple()
                    ->relationship('assignees', 'name')
                    ->minItems(1)
                    ->preload()
                    ->searchable()
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_assignees'))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DatePicker::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->required()
                    ->displayFormat('d M Y')
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_due_date'))
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
