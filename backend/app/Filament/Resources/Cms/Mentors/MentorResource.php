<?php

namespace App\Filament\Resources\Cms\Mentors;

use App\Filament\Resources\Cms\Mentors\Pages\CreateMentor;
use App\Filament\Resources\Cms\Mentors\Pages\EditMentor;
use App\Filament\Resources\Cms\Mentors\Pages\ListMentors;
use App\Filament\Resources\Cms\Mentors\Schemas\MentorForm;
use App\Filament\Resources\Cms\Mentors\Tables\MentorsTable;
use App\Models\Cms\Mentor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MentorResource extends Resource
{
    protected static ?string $model = Mentor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|\UnitEnum|null $navigationGroup = 'Team';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string { return __('admin.resources.mentor.p'); }
    public static function getModelLabel(): string { return __('admin.resources.mentor.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.mentor.p'); }

    // Visible to everyone, but only Admins can mutate (no explicit perm
    // in the catalog, so we gate on the role).
    public static function canViewAny(): bool { return auth()->check(); }
    public static function canCreate(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->isAdmin() ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->isAdmin() ?? false; }

    public static function form(Schema $schema): Schema
    {
        return MentorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MentorsTable::configure($table);
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
            'index' => ListMentors::route('/'),
            'create' => CreateMentor::route('/create'),
            'edit' => EditMentor::route('/{record}/edit'),
        ];
    }
}
