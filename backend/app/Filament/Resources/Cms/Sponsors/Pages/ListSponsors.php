<?php

namespace App\Filament\Resources\Cms\Sponsors\Pages;

use App\Filament\Resources\Cms\Sponsors\SponsorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSponsors extends ListRecords
{
    protected static string $resource = SponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
