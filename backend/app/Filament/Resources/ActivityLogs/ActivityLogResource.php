<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|\UnitEnum|null $navigationGroup = 'Membership';

    public static function getNavigationLabel(): string { return __('admin.resources.activity_log.p'); }
    public static function getModelLabel(): string { return __('admin.resources.activity_log.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.activity_log.p'); }

    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool { return auth()->user()?->isAdmin() ?? false; }
    public static function canAccess(): bool { return static::canViewAny(); }
    public static function shouldRegisterNavigation(): bool { return static::canViewAny(); }
    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return auth()->user()?->isAdmin() ?? false; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.common.when'))
                    ->dateTime('d M Y H:i')
                    ->since(),
                TextColumn::make('user.name')
                    ->label(__('admin.common.actor'))
                    ->placeholder(__('admin.common.system'))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('event')
                    ->label(__('admin.common.event'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => __('admin.activity.events.' . $state))
                    ->color(fn ($state) => match ($state) {
                        'created'  => 'success',
                        'updated'  => 'warning',
                        'deleted'  => 'danger',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        default    => 'gray',
                    }),
                TextColumn::make('subject_label')
                    ->label(__('admin.common.subject'))
                    ->wrap(),
                TextColumn::make('subject_type')
                    ->label(__('admin.common.type'))
                    ->formatStateUsing(fn ($state) => class_basename((string) $state))
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('admin.common.event'))
                    ->options([
                        'created'  => __('admin.activity.events.created'),
                        'updated'  => __('admin.activity.events.updated'),
                        'deleted'  => __('admin.activity.events.deleted'),
                        'accepted' => __('admin.activity.events.accepted'),
                        'rejected' => __('admin.activity.events.rejected'),
                    ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
