<?php

namespace App\Filament\Resources\TeamMemberGroups\Pages;

use App\Filament\Resources\TeamMemberGroups\TeamMemberGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMemberGroup extends CreateRecord
{
    protected static string $resource = TeamMemberGroupResource::class;

    /**
     * Form keeps name as two locale-specific text inputs but the column
     * is a single JSON {en, hu} map — combine on save / split on fill.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name'] = self::packLocaleMap($data, 'name');
        unset($data['name_en'], $data['name_hu']);
        return $data;
    }

    /** @return array{en?: string, hu?: string}|null */
    public static function packLocaleMap(array $data, string $key): ?array
    {
        $packed = [];
        if (! empty($data["{$key}_en"])) $packed['en'] = $data["{$key}_en"];
        if (! empty($data["{$key}_hu"])) $packed['hu'] = $data["{$key}_hu"];
        return $packed === [] ? null : $packed;
    }
}
