<?php

namespace App\Filament\Resources\Resources;

use App\Filament\Resources\Resources\Pages\CreateResource;
use App\Filament\Resources\Resources\Pages\EditResource;
use App\Filament\Resources\Resources\Pages\ListResources;
use App\Filament\Resources\Resources\Schemas\ResourceForm;
use App\Filament\Resources\Resources\Tables\ResourcesTable;
use App\Models\Resource as ResourceModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ResourceResource extends Resource
{
    protected static ?string $model = ResourceModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getRecordTitle(?\Illuminate\Database\Eloquent\Model $record): ?string
    {
        if (! $record) return null;
        /** @var \App\Models\Resource $record */
        return $record->resourceLabel();
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Advanced';

    public static function getNavigationLabel(): string { return __('admin.resources.all_resources.p'); }
    public static function getModelLabel(): string { return __('admin.resources.all_resources.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.all_resources.p'); }

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canCreate(): bool { return static::canViewAny(); }
    public static function canEdit($record): bool   { return static::canViewAny(); }
    public static function canDelete($record): bool { return static::canViewAny(); }
    public static function canDeleteAny(): bool     { return static::canViewAny(); }

    /**
     * Hidden from the sidebar — this is a raw-data inspector. Admins can
     * still hit /admin/resources directly when debugging.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ResourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ResourcesTable::configure($table);
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
            'index' => ListResources::route('/'),
            'create' => CreateResource::route('/create'),
            'edit' => EditResource::route('/{record}/edit'),
        ];
    }
}
