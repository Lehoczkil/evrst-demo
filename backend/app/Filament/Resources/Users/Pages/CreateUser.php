<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Notifications\TeamMemberAccountCreated;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $temporaryPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If the admin didn't pick a password, generate a temp one and
        // remember it so we can email it after the record is saved.
        if (empty($data['password'])) {
            $this->temporaryPassword = Str::password(12);
            $data['password'] = Hash::make($this->temporaryPassword);
        }
        $data['password_changed_at'] = null;
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->temporaryPassword) {
            $this->record->notify(new TeamMemberAccountCreated($this->temporaryPassword));
            Notification::make()
                ->title('Temporary password emailed')
                ->body('A login + temp password has been sent to ' . $this->record->email . '.')
                ->success()
                ->send();
        }
    }
}
