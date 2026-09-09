<?php

namespace App\Filament\Concerns;

use App\Models\TeamMember;
use App\Support\MemberLogin;
use App\Support\MemberLoginResult;
use Filament\Notifications\Notification;

/**
 * Runs MemberLogin::provision() from a Filament page and turns the result
 * into a panel notification, so the automatic path (create page) and the
 * manual one ("Create login" on the edit page) always say the same thing.
 */
trait ProvisionsMemberLogin
{
    protected function provisionMemberLogin(TeamMember $member): MemberLoginResult
    {
        $result = MemberLogin::provision($member);

        $this->reportMemberLogin($result, $member);

        return $result;
    }

    /**
     * Minting a panel account is an admin act — the "Create login" button
     * has always been admin-only, and team.create is a Manager permission,
     * so the automatic path must not become a way around that.
     */
    protected function canProvisionMemberLogin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function reportMemberLogin(MemberLoginResult $result, TeamMember $member): void
    {
        $login = $result->user?->email ?? $member->email ?? '—';

        match ($result->status) {
            MemberLogin::CREATED_SENT => $this->reportLoginCreated($login, $result->destination ?? $login),

            MemberLogin::CREATED_UNDELIVERABLE => Notification::make()
                ->title(__('admin.team.login_undeliverable'))
                ->body(__('admin.team.login_undeliverable_body', ['login' => $login]))
                ->warning()
                ->persistent()
                ->send(),

            MemberLogin::CREATED_MAIL_FAILED => Notification::make()
                ->title(__('admin.users.temp_send_failed'))
                ->body(__('admin.team.login_mail_failed_body', [
                    'login' => $login,
                    'error' => $result->error ?? '',
                ]))
                ->danger()
                ->persistent()
                ->send(),

            MemberLogin::LINKED_EXISTING => Notification::make()
                ->title(__('admin.team.login_linked'))
                ->body(__('admin.team.login_linked_body', ['login' => $login]))
                ->success()
                ->send(),

            MemberLogin::NO_ADDRESS => Notification::make()
                ->title(__('admin.team.login_none'))
                ->body(__('admin.team.login_no_address_body'))
                ->warning()
                ->persistent()
                ->send(),

            // ALREADY_LINKED — the admin picked a user by hand, or the row
            // already had one. Nothing happened, so say nothing.
            default => null,
        };
    }

    /**
     * MAIL_MAILER=log writes the password to the log instead of sending it.
     * Say so loudly — the same wording the Users table uses — or the admin
     * assumes the member has their credentials.
     */
    private function reportLoginCreated(string $login, string $destination): void
    {
        if (config('mail.default') === 'log') {
            Notification::make()
                ->title(__('admin.users.temp_logged'))
                ->body(__('admin.users.temp_logged_body', ['email' => $destination]))
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title(__('admin.team.login_created'))
            ->body(__('admin.team.login_created_body', [
                'login' => $login,
                'email' => $destination,
            ]))
            ->success()
            ->send();
    }
}
