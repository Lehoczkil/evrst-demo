<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\TeamMember;
use App\Support\AlumniStatus;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Alumni status: off the public site, still on the books, account
 * untouched.
 *
 * Backed by `team_members.left_at` rather than a flag of its own — that
 * column already drove the public API filter and the table's "Alumni"
 * filter. What is new is the one-click act.
 */
class AlumniStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();
    }

    private function member(array $overrides = []): TeamMember
    {
        return TeamMember::create(array_merge([
            'name' => 'Teszt Elek',
            'email' => 'elek.teszt@evrst.hu',
            'is_public' => true,
            'position' => 0,
        ], $overrides));
    }

    public function test_marking_someone_alumni_takes_them_off_the_public_site(): void
    {
        $member = $this->member();

        $this->getJson('/api/team/members')->assertOk()->assertJsonCount(1);

        AlumniStatus::mark($member);

        $this->getJson('/api/team/members')->assertOk()->assertJsonCount(0);
        $this->assertNotNull($member->fresh()->left_at);
    }

    /**
     * Alumni is a statement about the roster, not about access: someone who
     * has left may still need to sign in to hand work over.
     */
    public function test_it_does_not_touch_the_account_or_its_role(): void
    {
        $user = $this->makeMember(['email' => 'elek.teszt@evrst.hu']);
        $member = $this->member(['user_id' => $user->id]);

        AlumniStatus::mark($member);

        $user->refresh();

        $this->assertNotNull($user->id, 'the account must survive');
        $this->assertSame(Perm::ROLE_MEMBER, $user->role->key);
        $this->assertSame($user->id, $member->fresh()->user_id);
    }

    /**
     * `is_public` is a separate, deliberate setting — an active member can
     * opt out of the website. Marking alumni must not overwrite it, or the
     * way back would silently republish someone who had opted out.
     */
    public function test_it_leaves_the_public_opt_out_alone(): void
    {
        $member = $this->member(['is_public' => false]);

        AlumniStatus::mark($member);
        AlumniStatus::restore($member);

        $this->assertFalse($member->fresh()->is_public);
    }

    public function test_restoring_puts_them_back_on_the_public_site(): void
    {
        $member = $this->member(['left_at' => '2025-06-30']);

        $this->getJson('/api/team/members')->assertJsonCount(0);

        AlumniStatus::restore($member);

        $this->getJson('/api/team/members')->assertJsonCount(1);
        $this->assertNull($member->fresh()->left_at);
    }

    public function test_the_row_action_toggles_both_ways(): void
    {
        $member = $this->member();

        Livewire::actingAs($this->makeAdmin())
            ->test(ListTeamMembers::class)
            ->callTableAction('toggle_alumni', $member);

        $this->assertTrue(AlumniStatus::isAlumni($member->fresh()));

        Livewire::actingAs($this->makeAdmin())
            ->test(ListTeamMembers::class)
            ->callTableAction('toggle_alumni', $member->fresh());

        $this->assertFalse(AlumniStatus::isAlumni($member->fresh()));
    }

    public function test_the_edit_page_carries_the_same_switch(): void
    {
        $member = $this->member();

        Livewire::actingAs($this->makeAdmin())
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->callAction('toggle_alumni');

        $this->assertTrue(AlumniStatus::isAlumni($member->fresh()));
    }

    /** A cohort graduates together. */
    public function test_the_bulk_action_marks_everyone_selected(): void
    {
        $members = collect(['A', 'B', 'C'])->map(fn (string $n) => $this->member([
            'name' => "Teszt {$n}",
            'email' => strtolower($n) . '.teszt@evrst.hu',
        ]));

        Livewire::actingAs($this->makeAdmin())
            ->test(ListTeamMembers::class)
            ->callTableBulkAction('mark_alumni', $members);

        $this->assertSame(3, TeamMember::whereNotNull('left_at')->count());
        $this->getJson('/api/team/members')->assertJsonCount(0);
    }

    public function test_a_member_cannot_mark_anyone_alumni(): void
    {
        $member = $this->member();

        Livewire::actingAs($this->makeMember())
            ->test(ListTeamMembers::class)
            ->assertTableActionHidden('toggle_alumni', $member);
    }
}
