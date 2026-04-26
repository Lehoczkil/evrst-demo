<?php

namespace App\Filament\Resources\Cms\AboutProjects\Pages;

use App\Filament\Resources\Cms\AboutProjects\AboutProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAboutProjects extends ListRecords
{
    protected static string $resource = AboutProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
