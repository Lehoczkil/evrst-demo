<?php

namespace App\Filament\Resources\Items\Pages;

use App\Filament\Resources\Items\ItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListItems extends ListRecords
{
    protected static string $resource = ItemResource::class;

    public function getTitle(): string { return __('admin.resources.item.p'); }

    public function getHeading(): string { return __('admin.resources.item.p'); }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.items.create_new'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
