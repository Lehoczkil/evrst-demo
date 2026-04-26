<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ForceChangeProfile extends EditProfile
{
    /**
     * Layout the profile form into two sections — account info on top
     * (avatar + name + email) and a separate password card below — so it
     * looks like a proper "settings" page rather than the bare default.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profile')
                    ->description('How your name and avatar appear in the admin.')
                    ->components([
                        FileUpload::make('avatar')
                            ->label('Profile picture')
                            ->avatar()
                            ->image()
                            ->imageCropAspectRatio('1:1')
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                    ])
                    ->columns(2),
                Section::make('Password')
                    ->description('Set a new password — required on first sign-in, optional thereafter.')
                    ->components([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        $this->getCurrentPasswordFormComponent()->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Stamp password_changed_at every time the user actually rotates their
     * password. Combined with the RequirePasswordChange middleware this is
     * how a freshly-provisioned user gets unblocked from the panel.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! empty($data['password'])) {
            $data['password_changed_at'] = now();
        }
        return parent::mutateFormDataBeforeSave($data);
    }
}
