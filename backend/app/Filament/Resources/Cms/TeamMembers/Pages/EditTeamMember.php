<?php

namespace App\Filament\Resources\Cms\TeamMembers\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EditTeamMember extends EditRecord
{
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
        $record->groups()->sync(array_fill_keys($this->pendingGroupIds, ['is_primary' => false]));
        $record->setPrimaryGroup($this->pendingMainPositionId);
    }

    protected function getHeaderActions(): array
    {
        /** @var TeamMember $record */
        $record = $this->getRecord();

        return [
            Action::make('create_login')
                ->label('Create login')
                ->icon('heroicon-o-key')
                ->color('warning')
                ->visible(fn () => ! $record->user_id
                    && filled($record->email)
                    && (auth()->user()?->isAdmin() ?? false))
                ->requiresConfirmation()
                ->modalHeading('Provision an admin account?')
                ->modalDescription(fn () => 'A new Member-role login will be created for ' . ($record->email ?? '—') . ' and a temporary password emailed.')
                ->action(function () use ($record) {
                    $temp = Str::password(12);
                    $memberRole = Role::where('key', Perm::ROLE_MEMBER)->first();

                    $user = User::updateOrCreate(
                        ['email' => $record->email],
                        [
                            'name' => $record->name,
                            'password' => Hash::make($temp),
                            'role_id' => $memberRole?->id,
                            'password_changed_at' => null,
                        ],
                    );

                    $record->user_id = $user->id;
                    $record->save();

                    $route = $user->fresh('teamMember')->routeNotificationForMail(null);
                    $destination = $route ? array_key_first($route) : $user->email;

                    try {
                        \Illuminate\Support\Facades\Notification::sendNow($user, new TeamMemberAccountCreated($temp));
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title(__('admin.users.temp_send_failed'))
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                        $this->fillForm();
                        return;
                    }

                    if (config('mail.default') === 'log') {
                        Notification::make()
                            ->title(__('admin.users.temp_logged'))
                            ->body(__('admin.users.temp_logged_body', ['email' => $destination]))
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title(__('admin.users.temp_sent'))
                            ->body(__('admin.users.temp_sent_body', ['email' => $destination]))
                            ->success()
                            ->send();
                    }

                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
