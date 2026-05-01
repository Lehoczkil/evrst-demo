<?php

namespace App\Filament\Resources\Drawings\Tables;

use App\Filament\Resources\Drawings\DrawingResource;
use App\Models\Drawing;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class DrawingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.calendar.modal.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('admin.drawing.author'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—')->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.drawing.created'))
                    ->dateTime('d M Y H:i')
                    ->sortable()->toggleable(),
                TextColumn::make('size')
                    ->label(__('admin.drawing.size'))
                    ->formatStateUsing(fn (?int $state) => $state ? self::formatBytes($state) : '—')
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('width')
                    ->label(__('admin.drawing.dimensions'))
                    ->formatStateUsing(fn (?int $state, Drawing $r) => ($state && $r->height) ? "{$state}×{$r->height}" : '—')
                    ->color('gray')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('admin.drawing.author'))
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('admin.drawing.view'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Drawing $r) => $r->title)
                    ->modalWidth(\Filament\Support\Enums\Width::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('admin.calendar.modal.cancel'))
                    ->modalContent(fn (Drawing $r) => view('filament.resources.drawings.components.preview', [
                        'drawing' => $r,
                    ])),
                Action::make('edit')
                    ->label(__('admin.drawing.edit'))
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->visible(fn (Drawing $r) => DrawingResource::canEdit($r))
                    ->url(fn (Drawing $r) => DrawingResource::getUrl('create') . '?edit=' . $r->id),
                Action::make('duplicate')
                    ->label(__('admin.drawing.duplicate'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->visible(fn () => DrawingResource::canCreate())
                    ->url(fn (Drawing $r) => DrawingResource::getUrl('create') . '?from=' . $r->id),
                Action::make('download')
                    ->label(__('admin.drawing.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Drawing $r) {
                        $disk = Storage::disk($r->disk ?: 'public');
                        if (! $r->path || ! $disk->exists($r->path)) return;
                        return $disk->download(
                            $r->path,
                            preg_replace('/[^A-Za-z0-9_\-]+/', '_', $r->title) . '.png',
                        );
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Trait's deleting hook handles file cleanup.
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.drawing.empty_heading'))
            ->emptyStateDescription(__('admin.drawing.empty_body'))
            ->emptyStateIcon('heroicon-o-paint-brush');
    }

    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $val = (float) $bytes;
        while ($val >= 1024 && $i < count($units) - 1) {
            $val /= 1024;
            $i++;
        }
        return number_format($val, $i === 0 ? 0 : 1) . ' ' . $units[$i];
    }
}
