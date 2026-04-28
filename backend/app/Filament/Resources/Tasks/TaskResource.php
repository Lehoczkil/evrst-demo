<?php

namespace App\Filament\Resources\Tasks;

use App\Auth\Perm;
use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\KanbanBoard;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\RelationManagers\ChildrenRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\ProofsRelationManager;
use App\Filament\Resources\Tasks\Schemas\TaskForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Tasks';

    public static function getNavigationLabel(): string { return __('admin.resources.task.p'); }
    public static function getModelLabel(): string { return __('admin.resources.task.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.task.p'); }

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool { return auth()->check(); }
    public static function canCreate(): bool  { return auth()->user()?->can(Perm::TASKS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::TASKS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::TASKS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::TASKS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ChildrenRelationManager::class,
            ProofsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'kanban' => KanbanBoard::route('/kanban'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
