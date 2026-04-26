<?php

namespace App\Filament\Resources\Cms\TeamMembers\Pages;

use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTeamMembers extends ListRecords
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
