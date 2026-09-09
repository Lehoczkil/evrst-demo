<?php

namespace App\Filament\Resources\OnshapeModels\Pages;

use App\Filament\Resources\OnshapeModels\OnshapeModelResource;
use App\Jobs\ExportOnshapeModelToGlb;
use App\Models\OnshapeModel;
use App\Services\Onshape\Client as OnshapeClient;
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

                    if (! OnshapeClient::fromConfig()->isConfigured()) {
                        Notification::make()
                            ->title(__('admin.onshape.export_failed', ['reason' => '']))
                            ->body(__('admin.onshape.keys_missing'))
                            ->danger()
                            ->send();
                        return;
                    }

                    $record->forceFill([
                        'glb_status' => OnshapeModel::GLB_QUEUED,
                        'glb_error' => null,
                    ])->save();

                    // On the queue, not dispatchSync: this is an external
                    // API call plus a binary download that regularly takes
                    // 30-90s (capped at 3 minutes), which is far longer
                    // than a web request should be held open. The compose
                    // stack runs a dedicated `queue` container; with
                    // QUEUE_CONNECTION=sync it still runs inline, so a box
                    // without a worker degrades rather than breaks.
                    ExportOnshapeModelToGlb::dispatch($record->id, auth()->id());

                    Notification::make()
                        ->title(__('admin.onshape.export_queued'))
                        ->body(__('admin.onshape.export_queued_body'))
                        ->info()
                        ->send();

                    // Filament's partial form refresh doesn't re-evaluate
                    // the embed Section's visible() / viewData() closures,
                    // so the Three.js viewer wouldn't see a finished GLB
                    // until a manual page reload. A self-redirect on the
                    // current edit URL forces a fresh schema render.
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                }),
            Action::make('open_in_onshape')
                ->label(__('admin.onshape.view_in_onshape'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->share_url ?: $this->record->embed_url, true),
            DeleteAction::make(),
        ];
    }
}
