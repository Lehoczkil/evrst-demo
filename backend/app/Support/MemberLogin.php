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
 * The link between a roster row and the panel account that belongs to it,
 * in both directions.
 *
 * Every team member signs in with their org address, so adding someone to
 * team_members and giving them a User are two halves of the same act:
 *
 *   provision()  roster row  →  account. The Team members create page runs
 *                it automatically, "Create login" on the edit page and in
 *                the table run it on demand, `team:provision-login` runs
 *                it from the shell for rows that predate either.
 *   revoke()     roster row deleted → account deleted. Wired to
 *                TeamMember::deleted.
 *   detach()     account deleted → roster row survives, unpublished, and
 *                provisionable again. Wired to User::deleted.
 *
 * provision() is deliberately *not* a model observer — TeamSeeder writes
 * straight to the table and would mail the whole roster a fresh password
 * on every reseed. The other two are, because they send nothing and there
 * are four separate ways to delete a row.
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

    /**
     * May the current actor mint a panel account?
     *
     * Provisioning creates a `users` row, and Users is admin-only —
     * `team.create` is a Manager permission, so the automatic path on the
     * team-member create page must not become a way around that.
     */
    public static function canProvision(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

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

    /**
     * The roster row is going away, so the account goes with it.
     *
     * Membership is what the login is *for*: an account with no roster row
     * is someone who can still sign in to the panel and appears nowhere,
     * which is the state this exists to prevent. Called from
     * TeamMember::booted() rather than from the Filament actions, so it
     * covers the row action, the bulk action, the edit page's header
     * button, tinker and any future caller equally.
     *
     * Two accounts are never taken:
     *
     *  · Your own. Deleting the account you are signed in as ends the
     *    request in a redirect to the login screen, halfway through a bulk
     *    action, with no way to tell what else was processed.
     *  · An admin's. The roster is a list of people, not an authorisation
     *    list, and an admin account is very often the one that would have
     *    to grant itself back in. Removing someone's roster row must not
     *    be able to lock the panel.
     *
     * Returns the address of the account it deleted, or null when it left
     * one standing — the caller decides whether that is worth reporting.
     */
    public static function revoke(TeamMember $member): ?string
    {
        $user = $member->user;

        if (! $user || $user->getKey() === auth()->id() || $user->isAdmin()) {
            return null;
        }

        $email = (string) $user->email;

        // Null the link first. team_members.user_id is `nullOnDelete`, so
        // the database would do it anyway — but the in-memory model would
        // still be holding the stale id, and this row is about to be read
        // back by whatever is showing the delete notification.
        $member->forceFill(['user_id' => null])->saveQuietly();

        $user->delete();

        return $email;
    }

    /**
     * The account is going away but the person is not.
     *
     * Their roster row survives — history, a name, a photo, a group — but
     * it comes off the public site, because a member with no way to sign
     * in is someone who has been off-boarded rather than someone who is
     * merely unlisted. Making it explicit (rather than filtering the API
     * on `user_id`) keeps the toggle visible and reversible in the panel,
     * and leaves room for a member who legitimately has no login.
     *
     * `user_id` is nulled here as well as by the FK, which is what makes
     * "Create login" reappear on the row.
     */
    public static function detach(User $user): ?TeamMember
    {
        $member = $user->teamMember;

        if (! $member) {
            return null;
        }

        $member->forceFill([
            'user_id' => null,
            'is_public' => false,
        ])->saveQuietly();

        return $member;
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
