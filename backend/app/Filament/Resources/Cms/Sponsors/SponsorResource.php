<?php

namespace App\Filament\Resources\Cms\Sponsors;

use App\Auth\Perm;
use App\Filament\Resources\Cms\Sponsors\Pages\CreateSponsor;
use App\Filament\Resources\Cms\Sponsors\Pages\EditSponsor;
use App\Filament\Resources\Cms\Sponsors\Pages\ListSponsors;
use App\Filament\Resources\Cms\Sponsors\Schemas\SponsorForm;
use App\Filament\Resources\Cms\Sponsors\Tables\SponsorsTable;
use App\Models\Cms\Sponsor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SponsorResource extends Resource
{
    protected static ?string $model = Sponsor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 40;

    /**
     * Manager has no sponsor permissions, so the resource disappears
     * from the sidebar entirely for them. Members keep read-only view.
     */
    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        return $u->isAdmin()
            || $u->can(Perm::SPONSORS_CREATE)
            || $u->can(Perm::SPONSORS_EDIT)
            || $u->can(Perm::SPONSORS_DELETE)
            // Members are read-only viewers identified by their role key.
            || ($u->role?->key === Perm::ROLE_MEMBER);
    }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::SPONSORS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::SPONSORS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::SPONSORS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::SPONSORS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return SponsorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SponsorsTable::configure($table);
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
            'index' => ListSponsors::route('/'),
            'create' => CreateSponsor::route('/create'),
            'edit' => EditSponsor::route('/{record}/edit'),
        ];
    }
}
