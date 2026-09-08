<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignOrgLoginEmailsMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * The migration file returns its anonymous class, so it can be run
     * again by hand against rows created after RefreshDatabase migrated
     * an empty database.
     */
    private function runMigration(): void
    {
        (require database_path('migrations/2026_07_21_000000_assign_org_login_emails.php'))->up();
    }

    public function test_it_repoints_a_stub_login_to_the_org_address(): void
    {
        $user = $this->makeMember(['name' => 'Kerek Gábor', 'email' => 'kerek.gabor@evrst.test']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Kerek Gábor',
            'email' => 'kerek.gabor@evrst.test',
            'email_private' => 'kerek.gabo@gmail.com',
        ]);

        $this->runMigration();

        $this->assertSame('gabor.kerek@evrst.hu', $user->fresh()->email);
        $this->assertSame('gabor.kerek@evrst.hu', TeamMember::where('user_id', $user->id)->value('email'));
        // An existing private address is never overwritten.
        $this->assertSame('kerek.gabo@gmail.com', TeamMember::where('user_id', $user->id)->value('email_private'));
    }

    public function test_it_preserves_a_real_login_into_email_private(): void
    {
        $user = $this->makeMember(['name' => 'Nyári György', 'email' => 'gyurka@gmail.com']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Nyári György',
            'email' => 'nyari.gyorgy@evrst.test',
            'email_private' => null,
        ]);

        $this->runMigration();

        $this->assertSame('gyorgy.nyari@evrst.hu', $user->fresh()->email);
        // The only deliverable address we had is kept, not dropped.
        $this->assertSame('gyurka@gmail.com', TeamMember::where('user_id', $user->id)->value('email_private'));
    }

    public function test_it_never_touches_an_account_without_a_team_member(): void
    {
        $admin = $this->makeUser(Perm::ROLE_ADMIN, ['name' => 'Break Glass', 'email' => 'admin@evrst.test']);

        $this->runMigration();

        $this->assertSame('admin@evrst.test', $admin->fresh()->email);
    }

    public function test_it_suffixes_instead_of_colliding(): void
    {
        $first = $this->makeMember(['name' => 'Kovács János', 'email' => 'kovacs.janos@evrst.test']);
        TeamMember::create(['user_id' => $first->id, 'name' => 'Kovács János', 'email' => 'kovacs.janos@evrst.test']);

        $second = $this->makeMember(['name' => 'Kovács János', 'email' => 'kovacs.janos.2@evrst.test']);
        TeamMember::create(['user_id' => $second->id, 'name' => 'Kovács János', 'email' => 'kovacs.janos.2@evrst.test']);

        $this->runMigration();

        $emails = [$first->fresh()->email, $second->fresh()->email];
        sort($emails);

        $this->assertSame(['janos.kovacs.2@evrst.hu', 'janos.kovacs@evrst.hu'], $emails);
    }

    public function test_it_is_idempotent(): void
    {
        $user = $this->makeMember(['name' => 'Bába Kíra', 'email' => 'baba.kira@evrst.test']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Bába Kíra',
            'email' => 'baba.kira@evrst.test',
            'email_private' => 'babakira520@gmail.com',
        ]);

        $this->runMigration();
        $this->runMigration();

        $this->assertSame('kira.baba@evrst.hu', $user->fresh()->email);
        $this->assertSame(1, User::where('email', 'kira.baba@evrst.hu')->count());
    }
}
