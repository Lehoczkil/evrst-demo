<?php

namespace App\Filament\Resources\Cms\TeamMembers;

use App\Auth\Perm;
use App\Filament\Resources\Cms\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\Cms\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\Cms\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Resources\Cms\TeamMembers\Schemas\TeamMemberForm;
use App\Filament\Resources\Cms\TeamMembers\Tables\TeamMembersTable;
use App\Models\TeamMember;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'discord_nick'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->name ?? __('admin.resources.team_member.s'));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];
        if ($record->email) {
            $details['Email'] = (string) $record->email;
        }
        return $details;
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    public static function getNavigationLabel(): string { return __('admin.resources.team_member.p'); }
    public static function getModelLabel(): string { return __('admin.resources.team_member.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.team_member.p'); }

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::TEAM_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::TEAM_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::TEAM_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::TEAM_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return TeamMemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamMembersTable::configure($table);
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
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }
}
