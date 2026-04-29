<?php

namespace App\Filament\Resources\Cms\Events;

use App\Auth\Perm;
use App\Filament\Resources\Cms\Events\Pages\CreateEvent;
use App\Filament\Resources\Cms\Events\Pages\EditEvent;
use App\Filament\Resources\Cms\Events\Pages\ListEvents;
use App\Filament\Resources\Cms\Events\Schemas\EventForm;
use App\Filament\Resources\Cms\Events\Tables\EventsTable;
use App\Models\Cms\Event;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'title';

    // Title lives in the shared `resources` table inside JSON `payload`.
    // Search both locales of payload.title via Laravel's JSON-path
    // operator — the query grammar translates this to json_extract on
    // SQLite/MySQL and ->> on Postgres.
    public static function getGloballySearchableAttributes(): array
    {
        return ['payload->title->en', 'payload->title->hu'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->title ?? __('admin.resources.event.s'));
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];
        if ($record->status) {
            $details['Status'] = (string) $record->status;
        }
        if ($record->start_at) {
            $details['Date'] = $record->start_at->format('d M Y');
        }
        return $details;
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string { return __('admin.resources.event.p'); }
    public static function getModelLabel(): string { return __('admin.resources.event.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.event.p'); }

    public static function canViewAny(): bool { return true; }
    public static function canCreate(): bool  { return auth()->user()?->can(Perm::EVENTS_CREATE) ?? false; }
    public static function canEdit($record): bool   { return auth()->user()?->can(Perm::EVENTS_EDIT) ?? false; }
    public static function canDelete($record): bool { return auth()->user()?->can(Perm::EVENTS_DELETE) ?? false; }
    public static function canDeleteAny(): bool     { return auth()->user()?->can(Perm::EVENTS_DELETE) ?? false; }

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
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
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
