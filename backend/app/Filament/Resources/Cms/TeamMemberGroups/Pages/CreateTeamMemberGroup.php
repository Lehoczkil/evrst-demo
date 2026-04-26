<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Pages;

use App\Filament\Resources\Cms\TeamMemberGroups\TeamMemberGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMemberGroup extends CreateRecord
{
    protected static string $resource = TeamMemberGroupResource::class;
}
