<?php

namespace App\Filament\Resources\Tasks\Schemas;

use App\Auth\Perm;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Cache;

class TaskForm
{
    /**
     * True when this viewer may move the task along but not redefine it.
     *
     * TASKS_PROGRESS (the Member role) opens the edit page for a task you
     * are assigned to or supervise — so you can attach proof and change the
     * status. It is not permission to retitle the task, reassign it or move
     * its deadline, so every field that defines the task is locked. Filament
     * does not dehydrate a disabled field, so the stored values survive the
     * save untouched rather than depending on what the form posted.
     */
    public static function definitionLocked(?Task $record): bool
    {
        return $record !== null && ! (auth()->user()?->can(Perm::TASKS_EDIT) ?? false);
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('admin.tasks.section_details'))
                    ->columnSpanFull()
                    ->columns(12)
                    ->components([
                TextInput::make('title')
                    ->label(__('admin.common.title'))
                    ->required()
                    ->maxLength(180)
                    ->disabled(fn ($record) => self::definitionLocked($record))
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
                    ->disabled(fn ($record) => self::definitionLocked($record))
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
                    ->disabled(fn ($record) => self::definitionLocked($record))
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
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 6]),
                DatePicker::make('due_date')
                    ->label(__('admin.tasks.due_date'))
                    ->required()
                    ->displayFormat('d M Y')
                    ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.help.fields.task_due_date'))
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Select::make('priority')
                    ->label(__('admin.tasks.priority'))
                    ->options(fn () => collect(Task::priorities())
                        ->mapWithKeys(fn ($p) => [$p => __('admin.tasks.priorities.' . $p)])
                        ->all())
                    ->default(Task::PRIORITY_NORMAL)
                    ->required()
                    ->native(false)
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Select::make('category')
                    ->label(__('admin.tasks.category'))
                    ->options(fn () => collect(Task::categories())
                        ->mapWithKeys(fn ($c) => [$c => __('admin.tasks.categories.' . $c)])
                        ->all())
                    ->searchable()
                    ->native(false)
                    ->placeholder(__('admin.tasks.no_category'))
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                Select::make('parent_task_id')
                    ->label(__('admin.tasks.parent'))
                    ->options(function ($record) {
                        $q = Task::query()->orderBy('title');
                        if ($record) {
                            // Don't let a task pick itself or any descendant.
                            $q->where('id', '!=', $record->id);
                        }
                        return $q->pluck('title', 'id')->all();
                    })
                    ->searchable()
                    ->native(false)
                    ->placeholder(__('admin.tasks.no_parent'))
                    ->helperText(__('admin.tasks.parent_help'))
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 8]),
                TextInput::make('position')
                    ->label(__('admin.tasks.position'))
                    ->numeric()
                    ->default(0)
                    ->disabled(fn ($record) => self::definitionLocked($record))
                    ->columnSpan(['default' => 12, 'md' => 4]),
                    ]),
            ]);
    }
}
