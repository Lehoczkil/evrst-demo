<?php

namespace App\Filament\Resources\Cms\TeamMembers\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Cms\TeamMembers\TeamMemberResource;
use App\Models\Cms\TeamMember;
use App\Models\Role;
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

                    $user->notify(new TeamMemberAccountCreated($temp));

                    Notification::make()
                        ->title('Login created')
                        ->body('Temporary password emailed to ' . $user->email . '.')
                        ->success()
                        ->send();

                    $this->fillForm();
                }),
            DeleteAction::make(),
        ];
    }
}
