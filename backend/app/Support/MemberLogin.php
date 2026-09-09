<?php

namespace App\Support;

use App\Actions\IssueTempPassword;
use App\Auth\Perm;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Provisions the panel login that belongs to a roster row.
 *
 * Every team member signs in with their org address, so adding someone to
 * team_members and giving them a User are two halves of the same act. This
 * is the single implementation of that half: the Team members create page
 * runs it automatically, the "Create login" button on the edit page runs it
 * on demand, and `team:provision-login` runs it from the shell for rows
 * that predate either.
 *
 * Deliberately *not* a model observer — TeamSeeder writes straight to the
 * table and would mail the whole roster a fresh password on every reseed.
 */
final class MemberLogin
{
    /** Account created, temp password delivered. */
    public const CREATED_SENT = 'created_sent';

    /** Account created, but nothing on file we can actually mail. */
    public const CREATED_UNDELIVERABLE = 'created_undeliverable';

    /** Account created, the mailer threw. The login still exists. */
    public const CREATED_MAIL_FAILED = 'created_mail_failed';

    /** A User already held that address; linked it, changed no password. */
    public const LINKED_EXISTING = 'linked_existing';

    /** The row already points at a user — nothing to do. */
    public const ALREADY_LINKED = 'already_linked';

    /** No org address on the row and none derivable from the name. */
    public const NO_ADDRESS = 'no_address';

    public static function provision(TeamMember $member): MemberLoginResult
    {
        if ($member->user_id !== null) {
            return new MemberLoginResult(self::ALREADY_LINKED, $member->user);
        }

        // A row saved without an org address still needs a login, so mint
        // one the same way the accept-application form does rather than
        // bailing out and leaving the member with no way in.
        $login = filled($member->email)
            ? (string) $member->email
            : OrgEmail::uniqueForName((string) $member->name, ignoreTeamMemberId: $member->getKey());

        if ($login === null) {
            return new MemberLoginResult(self::NO_ADDRESS);
        }

        // The org address is the login, so an account already holding it is
        // this member — adopt it. Rotating its password here would lock out
        // someone who has already signed in and set their own.
        $existing = User::where('email', $login)->first();

        if ($existing) {
            self::attach($member, $existing, $login);

            return new MemberLoginResult(self::LINKED_EXISTING, $existing, $existing->deliveryEmail());
        }

        $temp = IssueTempPassword::generate();

        // password_changed_at stays null so RequirePasswordChange forces a
        // reset on first sign-in.
        $user = DB::transaction(function () use ($member, $login, $temp): User {
            $user = User::create([
                'name' => $member->name,
                'email' => $login,
                'password' => Hash::make($temp),
                'role_id' => Role::where('key', Perm::ROLE_MEMBER)->value('id'),
                'password_changed_at' => null,
            ]);

            self::attach($member, $user, $login);

            return $user;
        });

        // deliveryEmail() reads the teamMember relation; hand it the row we
        // just linked so it doesn't answer from a stale (unloaded) one.
        $user->setRelation('teamMember', $member);

        // Delivery — including the undeliverable-address refusal and the
        // MAIL_MAILER=log caveat — belongs to IssueTempPassword, the one
        // path a temporary password leaves this application by.
        $delivery = IssueTempPassword::deliver($user, $temp);

        return new MemberLoginResult(
            match ($delivery->status) {
                IssueTempPassword::SENT => self::CREATED_SENT,
                IssueTempPassword::FAILED => self::CREATED_MAIL_FAILED,
                default => self::CREATED_UNDELIVERABLE,
            },
            $user,
            $delivery->destination,
            $delivery->error,
        );
    }

    /** Point the roster row at the account, writing back a derived address. */
    private static function attach(TeamMember $member, User $user, string $login): void
    {
        $member->forceFill([
            'user_id' => $user->getKey(),
            'email' => $login,
        ])->save();
    }
}
