<?php

namespace App\Filament\Resources\Cms\AboutProjects;

use App\Auth\Perm;
use App\Filament\Resources\Cms\AboutProjects\Pages\CreateAboutProject;
use App\Filament\Resources\Cms\AboutProjects\Pages\EditAboutProject;
use App\Filament\Resources\Cms\AboutProjects\Pages\ListAboutProjects;
use App\Filament\Resources\Cms\AboutProjects\Schemas\AboutProjectForm;
use App\Filament\Resources\Cms\AboutProjects\Tables\AboutProjectsTable;
use App\Models\Cms\AboutProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AboutProjectResource extends Resource
{
    protected static ?string $model = AboutProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'About';

    public static function getNavigationLabel(): string { return __('admin.resources.project.p'); }
    public static function getModelLabel(): string { return __('admin.resources.project.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.project.p'); }

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::PROJECTS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::PROJECTS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::PROJECTS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::PROJECTS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return AboutProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAboutProjects::route('/'),
            'create' => CreateAboutProject::route('/create'),
            'edit' => EditAboutProject::route('/{record}/edit'),
        ];
    }
}
