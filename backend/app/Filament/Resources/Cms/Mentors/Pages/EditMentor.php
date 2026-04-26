<?php

namespace App\Filament\Resources\Cms\Mentors\Pages;

use App\Filament\Resources\Cms\Mentors\MentorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMentor extends EditRecord
{
    protected static string $resource = MentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
