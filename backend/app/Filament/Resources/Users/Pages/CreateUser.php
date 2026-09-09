<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\IssueTempPassword;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Support\TempPasswordReport;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $temporaryPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If the admin didn't pick a password, generate a temp one and
        // remember it so we can email it after the record is saved.
        if (empty($data['password'])) {
            $this->temporaryPassword = IssueTempPassword::generate();
            $data['password'] = Hash::make($this->temporaryPassword);

            // A password *we* generated has to be replaced by the account
            // holder, so leave the stamp null and let RequirePasswordChange
            // catch them on first sign-in.
            $data['password_changed_at'] = null;
        } else {
            // One the admin typed and handed over in person is already
            // theirs. The column defaults to null, so without this stamp
            // they would be bounced to the profile form and asked to change
            // the password they were just given.
            $data['password_changed_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (! $this->temporaryPassword) {
            return;
        }

        // Reports the address the mail actually went to — deliveryEmail(),
        // which is the private one while MAIL_DELIVER_TO_ORG is off. This
        // used to print the login address instead, and had no try/catch,
        // so a mailer error 500'd the page after the user was created.
        TempPasswordReport::flash(
            IssueTempPassword::deliver($this->record, $this->temporaryPassword),
        );
    }
}
