<?php

namespace App\Filament\Resources\MemberApplications;

use App\Auth\Perm;
use App\Filament\Resources\MemberApplications\Pages\AcceptMemberApplication;
use App\Filament\Resources\MemberApplications\Pages\EditMemberApplication;
use App\Filament\Resources\MemberApplications\Pages\ListMemberApplications;
use App\Filament\Resources\MemberApplications\Schemas\MemberApplicationForm;
use App\Filament\Resources\MemberApplications\Tables\MemberApplicationsTable;
use App\Models\MemberApplication;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberApplicationResource extends Resource
{
    protected static ?string $model = MemberApplication::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Membership';

    public static function getNavigationLabel(): string { return __('admin.resources.application.p'); }
    public static function getModelLabel(): string { return __('admin.resources.application.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.application.p'); }

    protected static ?int $navigationSort = 10;

    /**
     * Resource is hidden from anyone without an applications-related
     * permission. That's: Admin (all perms) — Manager + Member don't
     * see it.
     */
    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        return $u->can(Perm::APPLICATIONS_EDIT)
            || $u->can(Perm::APPLICATIONS_ACCEPT)
            || $u->can(Perm::APPLICATIONS_REFUSE);
    }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::APPLICATIONS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->isAdmin() ?? false; }

    public static function getNavigationBadge(): ?string
    {
        if (! static::canViewAny()) return null;
        $count = MemberApplication::where('status', MemberApplication::STATUS_PENDING)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return MemberApplicationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MemberApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMemberApplications::route('/'),
            'edit' => EditMemberApplication::route('/{record}/edit'),
            'accept' => AcceptMemberApplication::route('/{record}/accept'),
        ];
    }
}
