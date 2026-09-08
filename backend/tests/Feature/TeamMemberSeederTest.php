<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // TeamSeeder provisions User accounts and needs the role catalog.
        $this->seed(RoleSeeder::class);
    }

    public function test_seeder_produces_expected_roster(): void
    {
        $this->seed(TeamSeeder::class);

        $this->assertSame(21, TeamMember::query()->count());
        $this->assertSame(9, TeamMemberGroup::query()->count());

        $manager = TeamMemberGroup::where('slug', 'csapat-menedzser')->firstOrFail();
        $managerNames = $manager->members()->pluck('name')->all();
        $this->assertContains('Klabacsek Bálint', $managerNames);
        $this->assertContains('Bihari Bertalan', $managerNames);
    }

    public function test_the_operator_account_is_seeded_as_admin(): void
    {
        // TeamSeeder overwrites role_id on every run, so a promotion made
        // with `user:role` reverts unless the roster itself says admin.
        // Losing this locks the operator out of Users, Roles and
        // Applications, which are all gated on isAdmin().
        $this->seed(\Database\Seeders\TeamSeeder::class);

        $user = \App\Models\User::where('email', 'laszlo.lehoczki@evrst.hu')->first();

        $this->assertNotNull($user, 'the operator account must exist');
        $this->assertTrue($user->isAdmin());
    }

    public function test_the_roster_has_at_least_one_admin(): void
    {
        $this->seed(\Database\Seeders\TeamSeeder::class);

        $admins = \App\Models\User::whereHas('role', fn ($q) => $q->where('key', 'admin'))->count();

        $this->assertGreaterThan(0, $admins, 'an adminless roster cannot manage itself');
    }

    public function test_klabacsek_balint_has_expected_handles(): void
    {
        $this->seed(TeamSeeder::class);

        $balint = TeamMember::where('name', 'Klabacsek Bálint')->firstOrFail();

        $this->assertSame('e.1415', $balint->discord_username);
        $this->assertNull($balint->discord_id);
        $this->assertSame('klabacsekbalint@gmail.com', $balint->email_private);
    }
}
