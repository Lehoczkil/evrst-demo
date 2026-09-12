<?php

namespace App\Filament\Resources\Cms\Mentors\Pages;

use App\Filament\Concerns\FillsVirtualAttributes;
use App\Filament\Resources\Cms\Mentors\MentorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMentor extends EditRecord
{
    use FillsVirtualAttributes;

    protected static string $resource = MentorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
