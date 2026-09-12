<?php

namespace App\Filament\Resources\Cms\Events\Pages;

use App\Filament\Concerns\FillsVirtualAttributes;
use App\Filament\Resources\Cms\Events\EventResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    use FillsVirtualAttributes;

    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
