<?php

namespace App\Filament\Resources\Cms\AboutGoals\Pages;

use App\Filament\Resources\Cms\AboutGoals\AboutGoalResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAboutGoal extends EditRecord
{
    protected static string $resource = AboutGoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
