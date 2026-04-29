<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups;

use App\Filament\Resources\Cms\TeamMemberGroups\Pages\CreateTeamMemberGroup;
use App\Filament\Resources\Cms\TeamMemberGroups\Pages\EditTeamMemberGroup;
use App\Filament\Resources\Cms\TeamMemberGroups\Pages\ListTeamMemberGroups;
use App\Filament\Resources\Cms\TeamMemberGroups\Schemas\TeamMemberGroupForm;
use App\Filament\Resources\Cms\TeamMemberGroups\Tables\TeamMemberGroupsTable;
use App\Models\Cms\TeamMemberGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TeamMemberGroupResource extends Resource
{
    protected static ?string $model = TeamMemberGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $recordTitleAttribute = 'name';

    // Name lives in JSON payload as { en, hu }; search both locales.
    public static function getGloballySearchableAttributes(): array
    {
        return ['payload->name->en', 'payload->name->hu'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->name ?? __('admin.resources.position.s'));
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
