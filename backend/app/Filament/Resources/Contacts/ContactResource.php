<?php

namespace App\Filament\Resources\Contacts;

use App\Auth\Perm;
use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Resources\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $slug = 'contacts';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string { return __('admin.resources.contact.p'); }
    public static function getModelLabel(): string { return __('admin.resources.contact.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.contact.p'); }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];
        if ($record->email) {
            $details['Email'] = (string) $record->email;
        }
        if ($record->group?->name) {
            $details['Group'] = (string) $record->group->name;
        }
        return $details;
    }

    public static function canViewAny(): bool { return auth()->user()?->can(Perm::CONTACTS_VIEW) ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::CONTACTS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::CONTACTS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::CONTACTS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('group');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit'   => EditContact::route('/{record}/edit'),
        ];
    }
}
