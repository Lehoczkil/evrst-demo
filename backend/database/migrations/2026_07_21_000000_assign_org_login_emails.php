<?php

use App\Models\TeamMember;
use App\Models\User;
use App\Support\OrgEmail;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Give every team member their org address (laszlo.lehoczki@evrst.hu)
     * as both the team_members.email on record and the users.email they
     * sign in with.
     *
     * Deliberately surgical:
     *  - Accounts with no linked team member are never touched, which is
     *    what protects the break-glass admin@evrst.test login.
     *  - A member already on an org address keeps it (idempotent re-run).
     *  - The old login is preserved into email_private when it looks like a
     *    real personal inbox (i.e. not a *@evrst.test stub) and no private
     *    address is on file yet — otherwise repointing the login would cut
     *    off the only deliverable address we had for them.
     *  - Addresses are minted through OrgEmail::uniqueForName(), so a name
     *    collision suffixes (.2) instead of failing the unique index.
     *
     * On a fresh install this is a no-op: TeamSeeder already provisions
     * org logins. It exists for databases seeded before that change, where
     * the seed-once volume marker means the seeder won't run again.
     */
    public function up(): void
    {
        TeamMember::withTrashed()
            ->with('user')
            ->cursor()
            ->each(function (TeamMember $member): void {
                $user = $member->user;

                $org = OrgEmail::isOrgAddress($member->email)
                    ? $member->email
                    : OrgEmail::uniqueForName(
                        $member->name ?? '',
                        ignoreUserId: $user?->getKey(),
                        ignoreTeamMemberId: $member->getKey(),
                    );

                // No usable address (blank/garbage name, or 50 collisions
                // deep) — leave the row exactly as it is.
                if ($org === null) {
                    return;
                }

                if ($member->email !== $org) {
                    $member->forceFill(['email' => $org])->saveQuietly();
                }

                if (! $user) {
                    return;
                }

                $previous = $user->email;

                if ($previous === $org) {
                    return;
                }

                // Don't lose a deliverable address by overwriting it.
                if (
                    ! $member->email_private
                    && $previous
                    && ! str_ends_with(strtolower($previous), '@evrst.test')
                    && ! OrgEmail::isOrgAddress($previous)
                ) {
                    $member->forceFill(['email_private' => $previous])->saveQuietly();
                }

                $user->forceFill(['email' => $org])->saveQuietly();
            });
    }

    public function down(): void
    {
        // No-op: the org address is the canonical login now, and the
        // <slug>@evrst.test stubs it replaced aren't worth restoring.
    }
};
