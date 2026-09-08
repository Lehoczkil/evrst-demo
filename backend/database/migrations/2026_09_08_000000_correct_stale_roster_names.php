<?php

use App\Models\TeamMember;
use App\Support\OrgEmail;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Correct roster names that were seeded with a typo, and re-mint the
     * org login that was derived from them.
     *
     * OrgEmail derives the address from team_members.name, so fixing a name
     * in TeamSeeder after the first seed isn't enough: the seed-once volume
     * marker means the seeder never runs again, and the stale login sticks.
     * `Lehocki László` produced laszlo.lehocki@evrst.hu where the roster now
     * says `Lehoczki László` → laszlo.lehoczki@evrst.hu.
     *
     * A typo can't be derived algorithmically, so the corrections are
     * spelled out. Only rows whose name matches a listed stale value are
     * touched, which makes a re-run a no-op.
     *
     * The login is only repointed for members who have never signed in
     * (password_changed_at IS NULL). Once someone has set their own
     * password the address is a credential they've used, so the name is
     * corrected but the login is left alone — move it deliberately from
     * the team member edit page instead, which notifies the admin.
     *
     * @var array<string, string>  stale name => correct name
     */
    private const CORRECTIONS = [
        'Lehocki László' => 'Lehoczki László',
    ];

    public function up(): void
    {
        foreach (self::CORRECTIONS as $stale => $correct) {
            TeamMember::withTrashed()
                ->with('user')
                ->where('name', $stale)
                ->cursor()
                ->each(function (TeamMember $member) use ($correct): void {
                    $member->forceFill(['name' => $correct])->saveQuietly();

                    $user = $member->user;

                    if ($user) {
                        $user->forceFill(['name' => $correct])->saveQuietly();
                    }

                    // Never touch a login its owner has already used.
                    if ($user && $user->password_changed_at !== null) {
                        return;
                    }

                    $org = OrgEmail::uniqueForName(
                        $correct,
                        ignoreUserId: $user?->getKey(),
                        ignoreTeamMemberId: $member->getKey(),
                    );

                    // Blank result (unusable name, or 50 collisions deep) —
                    // keep whatever address the row already has.
                    if ($org === null) {
                        return;
                    }

                    if ($member->email !== $org) {
                        $member->forceFill(['email' => $org])->saveQuietly();
                    }

                    if ($user && $user->email !== $org) {
                        $user->forceFill(['email' => $org])->saveQuietly();
                    }
                });
        }
    }

    public function down(): void
    {
        // No-op: re-introducing a typo isn't a rollback anyone wants.
    }
};
