<?php

namespace App\Filament\Resources\Cms\Sponsors\Pages;

use App\Filament\Resources\Cms\Sponsors\SponsorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSponsor extends EditRecord
{
    protected static string $resource = SponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
