<?php

namespace App\Filament\Resources\OnshapeModels\Pages;

use App\Filament\Resources\OnshapeModels\OnshapeModelResource;
use App\Jobs\ExportOnshapeModelToGlb;
use App\Models\OnshapeModel;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOnshapeModel extends EditRecord
{
    protected static string $resource = OnshapeModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_glb')
                ->label(__('admin.onshape.export_glb'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    /** @var OnshapeModel $record */
                    $record = $this->record;
                    $record->forceFill(['glb_status' => OnshapeModel::GLB_QUEUED, 'glb_error' => null])->save();
                    ExportOnshapeModelToGlb::dispatch($record->id);
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
                ->url(fn () => $this->record->share_url ?: $this->record->embed_url, true),
            DeleteAction::make()
                ->before(fn () => $this->record?->deleteGlbFile()),
        ];
    }
}
