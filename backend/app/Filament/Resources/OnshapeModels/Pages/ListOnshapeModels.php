<?php

namespace App\Filament\Resources\OnshapeModels\Pages;

use App\Filament\Resources\OnshapeModels\OnshapeModelResource;
use Filament\Actions\CreateAction;
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
            CreateAction::make()->label(__('admin.onshape.new')),
        ];
    }
}
