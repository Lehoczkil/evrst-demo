<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Pages;

use App\Filament\Resources\Cms\TeamMemberGroups\TeamMemberGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamMemberGroups extends ListRecords
{
    protected static string $resource = TeamMemberGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
