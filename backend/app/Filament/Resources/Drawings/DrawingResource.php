<?php

namespace App\Filament\Resources\Drawings;

use App\Filament\Resources\Drawings\Pages\Draw;
use App\Filament\Resources\Drawings\Pages\ListDrawings;
use App\Filament\Resources\Drawings\Tables\DrawingsTable;
use App\Models\Drawing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DrawingResource extends Resource
{
    protected static ?string $model = Drawing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    public static function getNavigationLabel(): string
    {
        return __('admin.drawing.gallery_title');
    }

    public static function getModelLabel(): string
    {
        return __('admin.drawing.gallery_title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.drawing.gallery_title');
    }

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 60;

    /** Any signed-in user may browse + create drawings. */
    public static function canViewAny(): bool { return auth()->check(); }
    public static function canAccess(): bool { return auth()->check(); }
    public static function canCreate(): bool { return auth()->check(); }

    public static function canEdit($record): bool
    {
        $u = auth()->user();
        return $u && ($u->isAdmin() || $record->user_id === $u->id);
    }

    public static function canDelete($record): bool
    {
        return static::canEdit($record);
    }

    public static function table(Table $table): Table
    {
        return DrawingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDrawings::route('/'),
            'create' => Draw::route('/draw'),
        ];
    }
}
