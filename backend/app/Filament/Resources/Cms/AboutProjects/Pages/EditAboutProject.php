<?php

namespace App\Filament\Resources\Cms\AboutProjects\Pages;

use App\Filament\Concerns\FillsVirtualAttributes;
use App\Filament\Resources\Cms\AboutProjects\AboutProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAboutProject extends EditRecord
{
    use FillsVirtualAttributes;

    protected static string $resource = AboutProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
