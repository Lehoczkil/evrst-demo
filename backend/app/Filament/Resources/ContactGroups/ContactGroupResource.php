<?php

namespace App\Filament\Resources\ContactGroups;

use App\Auth\Perm;
use App\Filament\Resources\ContactGroups\Pages\CreateContactGroup;
use App\Filament\Resources\ContactGroups\Pages\EditContactGroup;
use App\Filament\Resources\ContactGroups\Pages\ListContactGroups;
use App\Filament\Resources\ContactGroups\Schemas\ContactGroupForm;
use App\Filament\Resources\ContactGroups\Tables\ContactGroupsTable;
use App\Models\ContactGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContactGroupResource extends Resource
{
    protected static ?string $model = ContactGroup::class;

    protected static ?string $slug = 'contact-groups';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 31;

    public static function getNavigationLabel(): string { return __('admin.resources.contact_group.p'); }
    public static function getModelLabel(): string { return __('admin.resources.contact_group.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.contact_group.p'); }

    public static function canViewAny(): bool { return auth()->user()?->can(Perm::CONTACTS_VIEW) ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::CONTACTS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::CONTACTS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return ContactGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContactGroups::route('/'),
            'create' => CreateContactGroup::route('/create'),
            'edit'   => EditContactGroup::route('/{record}/edit'),
        ];
    }
}
