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

    public function test_klabacsek_balint_has_expected_handles(): void
    {
        $this->seed(TeamSeeder::class);

        $balint = TeamMember::where('name', 'Klabacsek Bálint')->firstOrFail();

        $this->assertSame('e.1415', $balint->discord_username);
        $this->assertNull($balint->discord_id);
        $this->assertSame('klabacsekbalint@gmail.com', $balint->email_private);
    }
}
