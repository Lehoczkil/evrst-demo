<?php

namespace App\Filament\Resources\OnshapeModels\Tables;

use App\Jobs\ExportOnshapeModelToGlb;
use App\Models\OnshapeModel;
use App\Services\Onshape\Client as OnshapeClient;
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
                    ->wrap()->toggleable(),
                TextColumn::make('user.name')
                    ->label(__('admin.drawing.author'))
                    ->searchable()
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—')->toggleable(),
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
                    })->toggleable(),
                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime('d M Y H:i')
                    ->sortable()->toggleable(),
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
                        if (! OnshapeClient::fromConfig()->isConfigured()) {
                            Notification::make()
                                ->title(__('admin.onshape.export_failed', ['reason' => '']))
                                ->body(__('admin.onshape.keys_missing'))
                                ->danger()
                                ->send();
                            return;
                        }
                        $r->forceFill(['glb_status' => OnshapeModel::GLB_QUEUED, 'glb_error' => null])->save();
                        // Inline run so the table refreshes with the
                        // ready/failed state in one click instead of
                        // depending on a separate queue worker.
                        ExportOnshapeModelToGlb::dispatchSync($r->id);
                        $r->refresh();
                        if ($r->hasGlb()) {
                            Notification::make()->title(__('admin.onshape.export_done'))->success()->send();
                        } else {
                            Notification::make()
                                ->title(__('admin.onshape.export_failed', ['reason' => $r->glb_error ?: '']))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('open_in_onshape')
                    ->label(__('admin.onshape.view_in_onshape'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (OnshapeModel $r) => $r->share_url ?: $r->embed_url, true),
                DeleteAction::make(),
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
