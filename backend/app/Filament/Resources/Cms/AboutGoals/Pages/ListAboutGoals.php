<?php

namespace App\Filament\Resources\Cms\AboutGoals\Pages;

use App\Filament\Resources\Cms\AboutGoals\AboutGoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAboutGoals extends ListRecords
{
    protected static string $resource = AboutGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
