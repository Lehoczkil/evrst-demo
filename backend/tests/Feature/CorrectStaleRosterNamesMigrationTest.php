<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The one-shot correction for roster names that were seeded with a typo.
 * OrgEmail derives the login from the name, so the login has to move with
 * it — but only for members who have never signed in.
 */
class CorrectStaleRosterNamesMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function runMigration(): void
    {
        (require database_path('migrations/2026_09_08_000000_correct_stale_roster_names.php'))->up();
    }

    public function test_it_corrects_the_name_and_repoints_the_login(): void
    {
        $user = $this->makeMember([
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
        ]);
        // `?? now()` in makeUser() swallows a null override — clear it here.
        $user->forceFill(['password_changed_at' => null])->save();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
            'email_private' => 'lehoczkilaszlo2002@gmail.com',
        ]);

        $this->runMigration();

        $fresh = $user->fresh();
        $this->assertSame('Lehoczki László', $fresh->name);
        $this->assertSame('laszlo.lehoczki@evrst.hu', $fresh->email);

        $member = TeamMember::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Lehoczki László', $member->name);
        $this->assertSame('laszlo.lehoczki@evrst.hu', $member->email);
        // The deliverable address is untouched.
        $this->assertSame('lehoczkilaszlo2002@gmail.com', $member->email_private);
    }

    public function test_it_fixes_the_name_but_keeps_a_login_already_in_use(): void
    {
        $user = $this->makeMember([
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
            'password_changed_at' => now(),
        ]);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
        ]);

        $this->runMigration();

        $fresh = $user->fresh();
        $this->assertSame('Lehoczki László', $fresh->name, 'the display name is always corrected');
        $this->assertSame(
            'laszlo.lehocki@evrst.hu',
            $fresh->email,
            'a login its owner has already signed in with must not move silently',
        );
    }

    public function test_it_is_idempotent(): void
    {
        $user = $this->makeMember([
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
        ]);
        // `?? now()` in makeUser() swallows a null override — clear it here.
        $user->forceFill(['password_changed_at' => null])->save();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehocki László',
            'email' => 'laszlo.lehocki@evrst.hu',
        ]);

        $this->runMigration();
        $this->runMigration();

        $this->assertSame('laszlo.lehoczki@evrst.hu', $user->fresh()->email, 'no .2 suffix on a second run');
        $this->assertSame(1, TeamMember::where('user_id', $user->id)->count());
    }

    public function test_it_leaves_correctly_named_members_alone(): void
    {
        $user = $this->makeMember([
            'name' => 'Bába Kíra',
            'email' => 'kira.baba@evrst.hu',
        ]);
        $user->forceFill(['password_changed_at' => null])->save();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Bába Kíra',
            'email' => 'kira.baba@evrst.hu',
        ]);

        $this->runMigration();

        $this->assertSame('Bába Kíra', $user->fresh()->name);
        $this->assertSame('kira.baba@evrst.hu', $user->fresh()->email);
    }
}
