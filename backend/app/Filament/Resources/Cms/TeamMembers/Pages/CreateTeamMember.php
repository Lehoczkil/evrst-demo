<?php

namespace App\Filament\Resources\Cms\TeamMembers\Pages;

use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;
}
