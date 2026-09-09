<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Filament\Concerns\ProvisionsMemberLogin;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Models\TeamMember;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    use ProvisionsMemberLogin;

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
        $record->syncGroupAssignments($this->pendingGroupIds, $this->pendingMainPositionId);

        $this->provisionLogin($record);
    }

    /**
     * Joining the roster and getting a panel account are the same act, so
     * the login is minted here rather than left to a second trip through
     * the edit page — org address included, derived from the name when the
     * admin left the field blank.
     */
    private function provisionLogin(TeamMember $record): void
    {
        // A row added for the record (someone who has already left) is
        // history, not an onboarding — no account, no mail.
        if ($record->user_id !== null || filled($record->left_at)) {
            return;
        }

        if (! $this->canProvisionMemberLogin()) {
            Notification::make()
                ->title(__('admin.team.login_none'))
                ->body(__('admin.team.login_not_allowed_body'))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        $this->provisionMemberLogin($record);
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
