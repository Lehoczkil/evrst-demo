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

        // One member row per roster entry. Derived so a roster change is a
        // one-file edit, with a floor to catch an accidentally emptied list.
        $roster = (new \ReflectionClass(TeamSeeder::class))->getConstant('MEMBERS');
        $this->assertGreaterThan(10, count($roster), 'the roster looks truncated');

        $positions = (new \ReflectionClass(TeamSeeder::class))->getConstant('POSITIONS');
        $this->assertGreaterThan(5, count($positions), 'the org chart looks truncated');

        $this->assertSame(count($roster), TeamMember::query()->count());
        $this->assertSame(count($positions), TeamMemberGroup::query()->count());

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

    public function test_seeded_snowflakes_are_well_formed_and_unique(): void
    {
        $this->seed(TeamSeeder::class);

        $ids = TeamMember::whereNotNull('discord_id')->pluck('discord_id');

        $this->assertGreaterThan(10, $ids->count(), 'the collected snowflakes look lost');
        $this->assertSame($ids->count(), $ids->unique()->count(), 'a snowflake is on two rows');

        foreach ($ids as $id) {
            $this->assertMatchesRegularExpression('/^\d{17,20}$/', $id);
        }
    }

    public function test_reseeding_keeps_snowflakes_collected_at_runtime(): void
    {
        $this->seed(TeamSeeder::class);

        // Stand in for `discord:sync-ids` having run: a snowflake that
        // exists nowhere in the seeder's own data.
        $member = TeamMember::where('discord_username', 'e.1415')->firstOrFail();
        $member->update(['discord_id' => '506440416951009282']);

        // The seeder force-deletes the whole roster on every run, so
        // without the carry-over this is where every DM would go quiet.
        $this->seed(TeamSeeder::class);

        $this->assertSame(
            '506440416951009282',
            TeamMember::where('discord_username', 'e.1415')->value('discord_id'),
        );
    }

    public function test_klabacsek_balint_has_expected_handles(): void
    {
        $this->seed(TeamSeeder::class);

        $balint = TeamMember::where('name', 'Klabacsek Bálint')->firstOrFail();

        $this->assertSame('e.1415', $balint->discord_username);
        $this->assertSame('506440416951009282', $balint->discord_id);
        $this->assertSame('klabacsekbalint@gmail.com', $balint->email_private);
    }
}
