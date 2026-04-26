<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Pages;

use App\Filament\Resources\Cms\TeamMemberGroups\TeamMemberGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamMemberGroup extends EditRecord
{
    protected static string $resource = TeamMemberGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
