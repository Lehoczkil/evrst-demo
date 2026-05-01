<?php

namespace App\Filament\Resources\Cms\TeamMemberGroups\Pages;

use App\Filament\Resources\Cms\TeamMemberGroups\TeamMemberGroupResource;
use App\Models\TeamMemberGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTeamMemberGroup extends EditRecord
{
    protected static string $resource = TeamMemberGroupResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var TeamMemberGroup $record */
        $record = $this->record;
        $data['name_en'] = $record->name['en'] ?? null;
        $data['name_hu'] = $record->name['hu'] ?? null;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['name'] = CreateTeamMemberGroup::packLocaleMap($data, 'name');
        unset($data['name_en'], $data['name_hu']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
