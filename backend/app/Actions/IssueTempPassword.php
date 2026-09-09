<?php

namespace App\Actions;

use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use App\Support\OrgEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Str;

/**
 * The one way a temporary password leaves this application.
 *
 * There used to be five: the Users row action, the Users bulk action,
 * "Create login" on a team member, CreateUser and the accept-application
 * flow. They disagreed on all four things that matter — whether an
 * undeliverable address is refused, whether a failed send rolls the
 * password back, whether the mail is queued or sent inline, and which
 * address is reported back to the admin. Everything routes through here
 * now, so those answers are the same everywhere:
 *
 * - the destination is always {@see User::deliveryEmail()};
 * - an address we cannot deliver to is refused *before* the rotation,
 *   because replacing a working password with one nobody receives locks
 *   the member out;
 * - a rotation that fails to send is rolled back to the previous hash;
 * - the mail is sent with sendNow, so the admin sees a real result rather
 *   than "queued" silence, and it works on a box with no queue worker.
 */
final class IssueTempPassword
{
    /** Delivered (see TempPasswordResult::loggedNotSent() for MAIL_MAILER=log). */
    public const SENT = 'sent';

    /** Nowhere to send it — nothing was rotated, the account is untouched. */
    public const SKIPPED_UNDELIVERABLE = 'skipped_undeliverable';

    /** The mailer threw. A rotation was rolled back; a new account keeps its password. */
    public const FAILED = 'failed';

    /** How long a generated password is. */
    public const LENGTH = 12;

    public static function generate(): string
    {
        return Str::password(self::LENGTH);
    }

    /**
     * Replace an existing account's password with a fresh one and mail it.
     *
     * Refuses (without touching the account) when there is no deliverable
     * address, and restores the previous hash if the send throws.
     */
    public static function rotate(User $user): TempPasswordResult
    {
        $destination = self::destinationFor($user);

        if (! self::isDeliverable($destination)) {
            return new TempPasswordResult(self::SKIPPED_UNDELIVERABLE, $destination);
        }

        $previousPassword = $user->getRawOriginal('password');
        $previousChangedAt = $user->password_changed_at;

        $password = self::generate();
        $user->update([
            'password' => Hash::make($password),
            'password_changed_at' => null,
        ]);

        $result = self::send($user, $password, $destination);

        if ($result->status === self::FAILED) {
            // Nobody knows the new password, so leaving it in place would
            // lock the account. Put the working one back.
            $user->forceFill([
                'password' => $previousPassword,
                'password_changed_at' => $previousChangedAt,
            ])->saveQuietly();
        }

        return $result;
    }

    /**
     * Mail a password that was just minted for a brand-new account.
     *
     * No rollback here: the account did not exist a moment ago, so its
     * password is not something anyone can be locked out of. A failure
     * leaves a usable account whose password an admin can re-issue.
     */
    public static function deliver(User $user, string $password): TempPasswordResult
    {
        $destination = self::destinationFor($user);

        if (! self::isDeliverable($destination)) {
            return new TempPasswordResult(self::SKIPPED_UNDELIVERABLE, $destination);
        }

        return self::send($user, $password, $destination);
    }

    /** Where mail for this account actually goes. */
    public static function destinationFor(User $user): ?string
    {
        $user->loadMissing('teamMember');

        return $user->deliveryEmail();
    }

    /**
     * deliveryEmail() falls back to the login address when no private one is
     * on file. While MAIL_DELIVER_TO_ORG is off that fallback is an @evrst.hu
     * address, which is a sign-in name with no mailbox behind it — mail to it
     * bounces, so treat it as undeliverable.
     */
    public static function isDeliverable(?string $destination): bool
    {
        if ($destination === null) {
            return false;
        }

        return (bool) config('mail.deliver_to_org_addresses')
            || ! OrgEmail::isOrgAddress($destination);
    }

    private static function send(User $user, string $password, ?string $destination): TempPasswordResult
    {
        try {
            NotificationFacade::sendNow($user, new TeamMemberAccountCreated($password));
        } catch (\Throwable $e) {
            report($e);

            return new TempPasswordResult(self::FAILED, $destination, $e->getMessage());
        }

        return new TempPasswordResult(self::SENT, $destination);
    }
}
