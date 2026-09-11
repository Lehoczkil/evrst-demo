<?php

namespace App\Filament\Support;

use App\Models\TeamMember;
use App\Support\MemberLogin;
use App\Support\MemberLoginResult;
use Filament\Notifications\Notification;

/**
 * Turns a {@see MemberLoginResult} into the panel notification the admin
 * sees.
 *
 * Static, and a sibling of {@see TempPasswordReport}, because provisioning
 * is no longer only something a *page* does: the team members table has a
 * "Create login" row action too, and a row action's closure cannot reach
 * the protected methods of a page trait. Both paths flash the same words
 * because both end up here.
 */
final class MemberLoginReport
{
    public static function flash(MemberLoginResult $result, TeamMember $member): void
    {
        $login = $result->user?->email ?? $member->email ?? '—';

        match ($result->status) {
            MemberLogin::CREATED_SENT => self::created($login, $result->destination ?? $login),

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
    private static function created(string $login, string $destination): void
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
