<?php

namespace App\Filament\Resources\OnshapeModels\Pages;

use App\Filament\Resources\OnshapeModels\OnshapeModelResource;
use App\Services\Onshape\Client as OnshapeClient;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListOnshapeModels extends ListRecords
{
    protected static string $resource = OnshapeModelResource::class;

    public function getTitle(): string { return __('admin.resources.onshape_model.p'); }

    public function getHeading(): string { return __('admin.resources.onshape_model.p'); }

    public function getSubheading(): ?string { return __('admin.onshape.gallery_sub'); }

    protected function getHeaderActions(): array
    {
        return [
            // Pre-flight check before a live demo. Cheap GET against
            // /users/sessioninfo so the presenter can confirm the keys
            // and the network reachability without spending a translation
            // request on a real model.
            Action::make('test_connection')
                ->label(__('admin.onshape.test_connection'))
                ->icon('heroicon-o-signal')
                ->color('gray')
                ->action(function () {
                    $result = OnshapeClient::fromConfig()->ping();
                    if ($result['ok'] ?? false) {
                        Notification::make()
                            ->title(__('admin.onshape.test_ok'))
                            ->body(__('admin.onshape.test_ok_body', ['name' => $result['name']]))
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('admin.onshape.test_failed'))
                            ->body($result['error'] ?? '')
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make()->label(__('admin.onshape.new')),
        ];
    }
}
