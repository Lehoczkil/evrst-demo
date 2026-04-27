<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Membership';

    public static function getNavigationLabel(): string { return __('admin.resources.role.p'); }
    public static function getModelLabel(): string { return __('admin.resources.role.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.role.p'); }

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canAccess(): bool { return static::canViewAny(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return false; } // seeded only
    public static function canEdit($record): bool { return static::canViewAny(); }
    public static function canDelete($record): bool { return false; }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }
}
