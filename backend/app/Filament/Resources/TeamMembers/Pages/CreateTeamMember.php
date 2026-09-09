<?php

namespace App\Filament\Resources\Cms\TeamMembers\Pages;

use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use App\Models\TeamMember;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;

    /** @var array<int> */
    protected array $pendingGroupIds = [];

    protected ?int $pendingMainPositionId = null;

    /**
     * `group_ids` and `main_position_id` are virtual form fields backed
     * by the team_members <-> team_member_groups pivot. Pull them out
     * of $data before Filament tries to fill them as model attributes,
     * stash them, and let the model save with just the real columns.
     * The pivot is then synced in afterCreate().
     *
     * `degree_en` / `degree_hu` are split for the form but stored as
     * a single JSON {en, hu} column.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingGroupIds = array_values(array_filter((array) ($data['group_ids'] ?? []), fn ($v) => $v !== null && $v !== ''));
        $this->pendingMainPositionId = isset($data['main_position_id']) ? (int) $data['main_position_id'] : null;
        unset($data['group_ids'], $data['main_position_id']);

        $data['degree'] = self::packDegree($data);
        unset($data['degree_en'], $data['degree_hu']);

        return $data;
    }

    protected function afterCreate(): void
    {
        /** @var TeamMember $record */
        $record = $this->record;
        $record->groups()->sync(array_fill_keys($this->pendingGroupIds, ['is_primary' => false]));
        $record->setPrimaryGroup($this->pendingMainPositionId);
    }

    /** @return array{en?: string, hu?: string}|null */
    public static function packDegree(array $data): ?array
    {
        $packed = [];
        if (! empty($data['degree_en'])) $packed['en'] = $data['degree_en'];
        if (! empty($data['degree_hu'])) $packed['hu'] = $data['degree_hu'];
        return $packed === [] ? null : $packed;
    }
}
