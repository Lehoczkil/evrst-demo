<?php

namespace App\Filament\Resources\TeamMemberGroups;

use App\Filament\Resources\TeamMemberGroups\Pages\CreateTeamMemberGroup;
use App\Filament\Resources\TeamMemberGroups\Pages\EditTeamMemberGroup;
use App\Filament\Resources\TeamMemberGroups\Pages\ListTeamMemberGroups;
use App\Filament\Resources\TeamMemberGroups\Schemas\TeamMemberGroupForm;
use App\Filament\Resources\TeamMemberGroups\Tables\TeamMemberGroupsTable;
use App\Models\TeamMemberGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TeamMemberGroupResource extends Resource
{
    protected static ?string $model = TeamMemberGroup::class;

    /**
     * These moved out of App\Filament\Resources\Cms\ when the models
     * stopped being CMS-backed. The slug is pinned to the old path so
     * the /admin/cms/… URLs people have bookmarked, the route names,
     * and the help-modal keys that are looked up by route name all
     * keep working — only the PHP namespace changed.
     */
    protected static ?string $slug = 'cms/team-member-groups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'slug';

    public static function getGloballySearchableAttributes(): array
    {
        return ['slug', 'name->en', 'name->hu'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) (TeamMemberGroup::pickLocale($record->name) ?? $record->slug ?? __('admin.resources.position.s'));
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    public static function getNavigationLabel(): string { return __('admin.resources.position.p'); }
    public static function getModelLabel(): string { return __('admin.resources.position.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.position.p'); }

    protected static ?int $navigationSort = 20;

    public static function canViewAny(): bool { return auth()->check(); }
    public static function canCreate(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->isAdmin() ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->isAdmin() ?? false; }

    public static function form(Schema $schema): Schema
    {
        return TeamMemberGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamMemberGroupsTable::configure($table);
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
            'index' => ListTeamMemberGroups::route('/'),
            'create' => CreateTeamMemberGroup::route('/create'),
            'edit' => EditTeamMemberGroup::route('/{record}/edit'),
        ];
    }
}
