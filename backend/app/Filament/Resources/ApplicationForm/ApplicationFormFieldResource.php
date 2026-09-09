<?php

namespace App\Filament\Resources\ApplicationForm;

use App\Auth\Perm;
use App\Filament\Resources\ApplicationForm\Pages\CreateApplicationFormField;
use App\Filament\Resources\ApplicationForm\Pages\EditApplicationFormField;
use App\Filament\Resources\ApplicationForm\Pages\ListApplicationFormFields;
use App\Filament\Resources\ApplicationForm\Schemas\ApplicationFormFieldForm;
use App\Filament\Resources\ApplicationForm\Tables\ApplicationFormFieldsTable;
use App\Models\ApplicationFormField;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * The questions on the public join-us form.
 *
 * Gated on content.* rather than applications.*: this is the public-facing
 * form, not the submissions it collects — a Manager who maintains the site
 * copy can maintain the questions, without gaining sight of any applicant.
 */
class ApplicationFormFieldResource extends Resource
{
    protected static ?string $model = ApplicationFormField::class;

    protected static ?string $slug = 'application-form';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|\UnitEnum|null $navigationGroup = 'Membership';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'key';

    public static function getNavigationLabel(): string { return __('admin.resources.application_form.p'); }
    public static function getModelLabel(): string { return __('admin.resources.application_form.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.application_form.p'); }

    public static function canViewAny(): bool { return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false; }
    public static function canCreate(): bool  { return auth()->user()?->can(Perm::CONTENT_CREATE) ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can(Perm::CONTENT_EDIT) ?? false; }

    /** A system field carries the login the accept flow provisions from. */
    public static function canDelete($record): bool
    {
        return ! $record->is_system && (auth()->user()?->can(Perm::CONTENT_DELETE) ?? false);
    }

    public static function canDeleteAny(): bool { return false; }

    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }

    public static function form(Schema $schema): Schema
    {
        return ApplicationFormFieldForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApplicationFormFieldsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplicationFormFields::route('/'),
            'create' => CreateApplicationFormField::route('/create'),
            'edit' => EditApplicationFormField::route('/{record}/edit'),
        ];
    }
}
