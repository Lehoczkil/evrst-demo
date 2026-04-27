<?php

namespace App\Filament\Resources\Cms\AboutGoals;

use App\Auth\Perm;
use App\Filament\Resources\Cms\AboutGoals\Pages\CreateAboutGoal;
use App\Filament\Resources\Cms\AboutGoals\Pages\EditAboutGoal;
use App\Filament\Resources\Cms\AboutGoals\Pages\ListAboutGoals;
use App\Filament\Resources\Cms\AboutGoals\Schemas\AboutGoalForm;
use App\Filament\Resources\Cms\AboutGoals\Tables\AboutGoalsTable;
use App\Models\Cms\AboutGoal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AboutGoalResource extends Resource
{
    protected static ?string $model = AboutGoal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'About';

    public static function getNavigationLabel(): string { return __('admin.resources.goal.p'); }
    public static function getModelLabel(): string { return __('admin.resources.goal.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.goal.p'); }

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::GOALS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::GOALS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::GOALS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::GOALS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return AboutGoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AboutGoalsTable::configure($table);
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
            'index' => ListAboutGoals::route('/'),
            'create' => CreateAboutGoal::route('/create'),
            'edit' => EditAboutGoal::route('/{record}/edit'),
        ];
    }
}
