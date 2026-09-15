<?php

namespace Tests\Feature;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\TeamMember;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Part-time is its own fact about a member.
 *
 * Not a position, not a group, not a shade of `left_at`: someone can be
 * part-time in any role, and part-time is not a kind of leaving. Everyone
 * starts full-time and only becomes part-time because someone said so, so
 * the default has to hold for rows that predate the column as well as for
 * rows created without thinking about it.
 */
class PartTimeMemberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Mail::fake();
        Bus::fake();
    }

    public function test_a_member_is_full_time_unless_someone_says_otherwise(): void
    {
        $member = TeamMember::create(['name' => 'Teljes Tamás']);

        $this->assertFalse($member->is_part_time);
        $this->assertFalse($member->fresh()->is_part_time, 'the column default must agree with the model default');
    }

    public function test_it_can_be_turned_on_and_off_again(): void
    {
        $member = TeamMember::create(['name' => 'Rész Rita']);

        $member->update(['is_part_time' => true]);
        $this->assertTrue($member->fresh()->is_part_time);

        $member->update(['is_part_time' => false]);
        $this->assertFalse($member->fresh()->is_part_time);
    }

    public function test_it_is_independent_of_leaving_and_of_visibility(): void
    {
        $member = TeamMember::create([
            'name' => 'Rész Robi',
            'is_part_time' => true,
            'left_at' => now(),
            'is_public' => false,
        ]);

        // Changing one must not disturb the other two.
        $member->update(['left_at' => null, 'is_public' => true]);

        $this->assertTrue($member->fresh()->is_part_time);
    }

    public function test_the_admin_can_set_it_on_the_create_form(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(CreateTeamMember::class)
            ->fillForm(['name' => 'Új Nóra', 'is_part_time' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(TeamMember::where('name', 'Új Nóra')->firstOrFail()->is_part_time);
    }

    public function test_the_table_can_filter_to_each_group(): void
    {
        $part = TeamMember::create(['name' => 'Rész Rita', 'is_part_time' => true]);
        $full = TeamMember::create(['name' => 'Teljes Tamás']);

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListTeamMembers::class)
            ->assertCanSeeTableRecords([$part, $full])
            ->filterTable('is_part_time', true)
            ->assertCanSeeTableRecords([$part])
            ->assertCanNotSeeTableRecords([$full])
            ->filterTable('is_part_time', false)
            ->assertCanSeeTableRecords([$full])
            ->assertCanNotSeeTableRecords([$part]);
    }

    public function test_the_public_api_carries_it(): void
    {
        TeamMember::create(['name' => 'Rész Rita', 'is_part_time' => true, 'is_public' => true]);
        TeamMember::create(['name' => 'Teljes Tamás', 'is_public' => true]);

        $rows = collect($this->getJson('/api/team/members')->assertOk()->json())
            ->keyBy('name');

        $this->assertTrue($rows['Rész Rita']['is_part_time']);
        $this->assertFalse($rows['Teljes Tamás']['is_part_time']);
    }
}
