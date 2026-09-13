<?php

namespace App\Filament\Resources\TeamMembers\Pages;

use App\Auth\Perm;
use App\Filament\Concerns\ProvisionsMemberLogin;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\AlumniStatus;
use App\Support\OrgEmail;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTeamMember extends EditRecord
{
    use ProvisionsMemberLogin;

    protected static string $resource = TeamMemberResource::class;

    /** @var array<int> */
    protected array $pendingGroupIds = [];

    protected ?int $pendingMainPositionId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var TeamMember $record */
        $record = $this->record;
        $record->loadMissing('groups');

        $data['group_ids'] = $record->groups->pluck('id')->all();
        $primary = $record->groups->firstWhere('pivot.is_primary', true);
        $data['main_position_id'] = $primary?->id;

        $data['degree_en'] = $record->degree['en'] ?? null;
        $data['degree_hu'] = $record->degree['hu'] ?? null;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingGroupIds = array_values(array_filter((array) ($data['group_ids'] ?? []), fn ($v) => $v !== null && $v !== ''));
        $this->pendingMainPositionId = isset($data['main_position_id']) ? (int) $data['main_position_id'] : null;
        unset($data['group_ids'], $data['main_position_id']);

        $data['degree'] = CreateTeamMember::packDegree($data);
        unset($data['degree_en'], $data['degree_hu']);

        return $data;
    }

    protected function afterSave(): void
    {
        /** @var TeamMember $record */
        $record = $this->record;
        $record->syncGroupAssignments($this->pendingGroupIds, $this->pendingMainPositionId);

        $this->syncLoginEmail($record);
    }

    /**
     * The org address is the login, so editing it here has to move
     * users.email too — otherwise the member would still be signing in
     * with the old address while the panel shows the new one.
     *
     * Bails out if another account already holds the address rather than
     * blowing up on the unique index, and tells the admin why.
     *
     * Admin-only at the source, not just in the form: rewriting someone
     * else's login address is an account takeover, and `team.edit` — which
     * is all TeamMemberResource::canEdit() asks for — is a Manager
     * permission. The form no longer dehydrates `email`, so a non-admin
     * can never get here with a changed address; the abort is the
     * backstop that keeps that true if the form changes.
     */
    private function syncLoginEmail(TeamMember $record): void
    {
        $user = $record->user;

        if (! $user || ! filled($record->email) || $user->email === $record->email) {
            return;
        }

        // Only when *this* save moved the address. Healing drift the other
        // way round (the roster row was already ahead of the account) is
        // harmless and must not 403 a manager who touched another field.
        if ($record->wasChanged('email')) {
            abort_unless(auth()->user()?->isAdmin() ?? false, 403);
        }

        if (User::where('email', $record->email)->whereKeyNot($user->getKey())->exists()) {
            Notification::make()
                ->title(__('admin.team.login_email_conflict'))
                ->body(__('admin.team.login_email_conflict_body', ['email' => $record->email]))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        $previous = $user->email;
        $user->forceFill(['email' => $record->email])->save();

        Notification::make()
            ->title(__('admin.team.login_email_synced'))
            ->body(__('admin.team.login_email_synced_body', [
                'old' => $previous,
                'new' => $record->email,
            ]))
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        /** @var TeamMember $record */
        $record = $this->getRecord();

        return [
            Action::make('create_login')
                ->label(__('admin.team.login_create'))
                ->icon('heroicon-o-key')
                ->color('warning')
                // No `filled($record->email)` guard any more: a row saved
                // without an org address gets one derived from the name,
                // which is exactly the case that needs this button.
                ->visible(fn () => ! $record->user_id && $this->canProvisionMemberLogin())
                ->requiresConfirmation()
                ->modalHeading(__('admin.team.login_create_modal'))
                ->modalDescription(fn () => __('admin.team.login_create_modal_body', [
                    'email' => filled($record->email)
                        ? $record->email
                        : (OrgEmail::forName((string) $record->name) ?? '—'),
                ]))
                ->action(function () use ($record) {
                    $this->provisionMemberLogin($record);
                    $this->fillForm();
                }),

            // Same switch as the row action on the table — see
            // App\Support\AlumniStatus. Refills the form afterwards so the
            // "Left at" field reflects what the button just did.
            Action::make('toggle_alumni')
                ->label(fn () => AlumniStatus::isAlumni($record)
                    ? __('admin.team.alumni_restore')
                    : __('admin.team.alumni_mark'))
                ->icon(fn () => AlumniStatus::isAlumni($record)
                    ? 'heroicon-o-arrow-uturn-left'
                    : 'heroicon-o-academic-cap')
                ->color('gray')
                ->visible(fn () => auth()->user()?->can(Perm::TEAM_EDIT) ?? false)
                ->requiresConfirmation()
                ->modalHeading(fn () => AlumniStatus::isAlumni($record)
                    ? __('admin.team.alumni_restore_modal', ['name' => $record->name])
                    : __('admin.team.alumni_mark_modal', ['name' => $record->name]))
                ->modalDescription(fn () => AlumniStatus::isAlumni($record)
                    ? __('admin.team.alumni_restore_body')
                    : __('admin.team.alumni_mark_body'))
                ->action(function () use ($record) {
                    AlumniStatus::toggle($record);

                    Notification::make()
                        ->title(AlumniStatus::isAlumni($record)
                            ? __('admin.team.alumni_marked', ['name' => $record->name])
                            : __('admin.team.alumni_restored', ['name' => $record->name]))
                        ->success()
                        ->send();

                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
