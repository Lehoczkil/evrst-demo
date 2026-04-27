<?php

namespace App\Filament\Resources\OnshapeModels\Pages;

use App\Filament\Resources\OnshapeModels\OnshapeModelResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOnshapeModel extends EditRecord
{
    protected static string $resource = OnshapeModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('open_in_onshape')
                ->label(__('admin.onshape.view_in_onshape'))
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn () => $this->record->share_url ?: $this->record->embed_url, true),
            DeleteAction::make(),
        ];
    }
}
