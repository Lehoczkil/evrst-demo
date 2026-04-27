<?php

namespace App\Filament\Resources\OnshapeModels\Tables;

use App\Jobs\ExportOnshapeModelToGlb;
use App\Models\OnshapeModel;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OnshapeModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.common.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label(__('admin.drawing.author'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—'),
                TextColumn::make('document_id')
                    ->label(__('admin.onshape.document_id'))
                    ->limit(12)
                    ->color('gray')
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('glb_status')
                    ->label(__('admin.onshape.glb_status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state, OnshapeModel $r) => match (true) {
                        $state === OnshapeModel::GLB_RUNNING || $state === OnshapeModel::GLB_QUEUED => __('admin.onshape.glb_running'),
                        $state === OnshapeModel::GLB_FAILED => __('admin.onshape.glb_failed'),
                        $r->hasGlb() => __('admin.onshape.glb_ready'),
                        default => __('admin.onshape.glb_not_exported'),
                    })
                    ->color(fn (?string $state, OnshapeModel $r) => match (true) {
                        $state === OnshapeModel::GLB_FAILED => 'danger',
                        $state === OnshapeModel::GLB_RUNNING || $state === OnshapeModel::GLB_QUEUED => 'warning',
                        $r->hasGlb() => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('admin.drawing.author'))
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('export_glb')
                    ->label(__('admin.onshape.export_glb'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (OnshapeModel $r) {
                        $r->forceFill(['glb_status' => OnshapeModel::GLB_QUEUED, 'glb_error' => null])->save();
                        ExportOnshapeModelToGlb::dispatch($r->id);
                        Notification::make()
                            ->title(__('admin.onshape.export_queued'))
                            ->body(__('admin.onshape.export_queued_body'))
                            ->success()
                            ->send();
                    }),
                Action::make('open_in_onshape')
                    ->label(__('admin.onshape.view_in_onshape'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (OnshapeModel $r) => $r->share_url ?: $r->embed_url, true),
                DeleteAction::make()
                    ->before(fn (OnshapeModel $r) => $r->deleteGlbFile()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.onshape.empty_heading'))
            ->emptyStateDescription(__('admin.onshape.empty_body'))
            ->emptyStateIcon('heroicon-o-cube');
    }
}
