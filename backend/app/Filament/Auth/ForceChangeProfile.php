<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
                Section::make(__('admin.profile.section'))
                    ->description(__('admin.profile.section_help'))
                    ->components([
                        FileUpload::make('avatar')
                            ->label(__('admin.profile.avatar'))
                            ->avatar()
                            ->image()
                            ->imageCropAspectRatio('1:1')
                            ->disk('public')
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->columnSpanFull(),
                        $this->getNameFormComponent(),
                        // The email IS the login — the org address handed
                        // out by the team — so only an admin may move it.
                        // Members change their password here, not their
                        // identity. Not dehydrated when locked, so the
                        // value can't be smuggled in via the payload.
                        $this->getEmailFormComponent()
                            ->disabled(fn () => ! static::canEditEmail())
                            ->dehydrated(fn () => static::canEditEmail())
                            ->helperText(fn () => static::canEditEmail()
                                ? null
                                : __('admin.profile.email_locked')),
                    ])
                    ->columns(2),
                Section::make(__('admin.users.password'))
                    ->description(__('admin.profile.password_help'))
                    ->components([
                        $this->getPasswordFormComponent(),
                        // Filament hides the confirmation + current-password
                        // inputs until `password` has a value, and the reveal
                        // rides on the password field's 500 ms live debounce.
                        // On this page that reads as a broken form: a member
                        // arriving from the temp-password mail sees a single
                        // "New password" box, types it, submits before the
                        // debounce fires, and only meets the other two fields
                        // through a validation error. Show all three up front
                        // and move the conditions onto required() — the rules
                        // are non-implicit, so an empty box is simply skipped
                        // when someone edits only their name or avatar.
                        $this->getPasswordConfirmationFormComponent()
                            ->visible()
                            ->required(fn (Get $get): bool => filled($get('password'))),
                        $this->getCurrentPasswordFormComponent()
                            ->visible()
                            ->required(fn (Get $get): bool => filled($get('password'))
                                || $get('email') !== $this->getUser()->getAttributeValue('email'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    private static function canEditEmail(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
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
