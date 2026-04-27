<?php

namespace App\Filament\Resources\OnshapeModels;

use App\Auth\Perm;
use App\Filament\Resources\OnshapeModels\Pages\CreateOnshapeModel;
use App\Filament\Resources\OnshapeModels\Pages\EditOnshapeModel;
use App\Filament\Resources\OnshapeModels\Pages\ListOnshapeModels;
use App\Filament\Resources\OnshapeModels\Schemas\OnshapeModelForm;
use App\Filament\Resources\OnshapeModels\Tables\OnshapeModelsTable;
use App\Models\OnshapeModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OnshapeModelResource extends Resource
{
    protected static ?string $model = OnshapeModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 70;

    public static function getNavigationLabel(): string { return __('admin.resources.onshape_model.p'); }
    public static function getModelLabel(): string { return __('admin.resources.onshape_model.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.onshape_model.p'); }

    /**
     * Mirrors the SponsorResource pattern: Admin always; specific perm
     * for each action; Members get read-only entry via the role key.
     */
    public static function canViewAny(): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        return $u->isAdmin()
            || $u->can(Perm::MODELS_VIEW)
            || $u->can(Perm::MODELS_CREATE)
            || $u->can(Perm::MODELS_EDIT)
            || $u->can(Perm::MODELS_DELETE)
            || ($u->role?->key === Perm::ROLE_MEMBER);
    }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::MODELS_CREATE) ?? false; }
    public static function canEdit($record): bool { return auth()->user()?->can(Perm::MODELS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::MODELS_DELETE) ?? false; }
    public static function canDeleteAny(): bool { return auth()->user()?->can(Perm::MODELS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return OnshapeModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OnshapeModelsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnshapeModels::route('/'),
            'create' => CreateOnshapeModel::route('/create'),
            'edit' => EditOnshapeModel::route('/{record}/edit'),
        ];
    }
}
