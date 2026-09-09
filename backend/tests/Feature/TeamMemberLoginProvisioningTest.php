<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Adding someone to the roster and giving them a way in are the same act:
 * the create page mints the Member login and mails the temp password.
 */
class TeamMemberLoginProvisioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    /** @param array<string, mixed> $data */
    private function create(array $data, ?User $actor = null): void
    {
        Livewire::actingAs($actor ?? $this->makeAdmin())
            ->test(CreateTeamMember::class)
            ->fillForm(array_merge([
                'name' => 'Pencz Máté',
                'email_private' => 'pencz.mate@example.test',
            ], $data))
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_creating_a_team_member_provisions_a_member_login_and_mails_the_password(): void
    {
        $this->create(['email' => 'mate.pencz@evrst.hu']);

        $user = User::where('email', 'mate.pencz@evrst.hu')->firstOrFail();
        $this->assertSame(Perm::ROLE_MEMBER, $user->role->key);
        // Forced through the first-login password change.
        $this->assertNull($user->password_changed_at);

        $member = TeamMember::where('name', 'Pencz Máté')->firstOrFail();
        $this->assertSame($user->id, $member->user_id);

        Notification::assertSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_a_blank_org_address_is_derived_from_the_name(): void
    {
        $this->create(['email' => null]);

        $member = TeamMember::where('name', 'Pencz Máté')->firstOrFail();
        $this->assertSame('mate.pencz@evrst.hu', $member->email);
        $this->assertSame('mate.pencz@evrst.hu', $member->user->email);
    }

    public function test_a_taken_org_address_is_derived_around_rather_than_colliding(): void
    {
        $this->makeMember(['name' => 'Pencz Máté', 'email' => 'mate.pencz@evrst.hu']);

        $this->create(['email' => null]);

        $member = TeamMember::where('name', 'Pencz Máté')->firstOrFail();
        $this->assertSame('mate.pencz.2@evrst.hu', $member->email);
    }

    public function test_it_creates_the_login_but_sends_nothing_when_no_private_address_is_on_file(): void
    {
        // deliveryEmail() would fall back to the @evrst.hu login, which is a
        // sign-in name with no mailbox behind it — mail to it bounces.
        $this->create(['email' => 'gyorgy.nyari@evrst.hu', 'email_private' => null]);

        $user = User::where('email', 'gyorgy.nyari@evrst.hu')->firstOrFail();
        $this->assertNotNull($user->teamMember);

        Notification::assertNotSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_an_existing_account_on_that_address_is_linked_not_rotated(): void
    {
        $existing = $this->makeMember(['email' => 'mate.pencz@evrst.hu']);
        $hash = $existing->password;

        $this->create(['email' => 'mate.pencz@evrst.hu']);

        $member = TeamMember::where('name', 'Pencz Máté')->firstOrFail();
        $this->assertSame($existing->id, $member->user_id);

        $fresh = $existing->fresh();
        $this->assertSame($hash, $fresh->password, 'linking must not rotate a password already in use');
        $this->assertNotNull($fresh->password_changed_at);
        Notification::assertNotSentTo($existing, TeamMemberAccountCreated::class);
    }

    public function test_a_member_who_has_already_left_gets_no_login(): void
    {
        // A row added for the record is history, not an onboarding.
        $this->create(['email' => 'mate.pencz@evrst.hu', 'left_at' => '2025-06-30']);

        $this->assertNull(TeamMember::where('name', 'Pencz Máté')->firstOrFail()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'mate.pencz@evrst.hu']);
    }

    public function test_a_manager_cannot_mint_a_login_through_the_roster(): void
    {
        // team.create is a Manager permission but Users is admin-only, so
        // the automatic path must not become a way around that.
        $this->create(['email' => 'mate.pencz@evrst.hu'], $this->makeManager());

        $this->assertNull(TeamMember::where('name', 'Pencz Máté')->firstOrFail()->user_id);
        $this->assertDatabaseMissing('users', ['email' => 'mate.pencz@evrst.hu']);
    }

    public function test_the_edit_page_can_provision_a_login_for_a_row_with_no_org_address(): void
    {
        $member = TeamMember::create([
            'name' => 'Pencz Máté',
            'email_private' => 'pencz.mate@example.test',
        ]);

        Livewire::actingAs($this->makeAdmin())
            ->test(EditTeamMember::class, ['record' => $member->id])
            ->callAction('create_login');

        $member->refresh();
        $this->assertSame('mate.pencz@evrst.hu', $member->email);
        $this->assertNotNull($member->user_id);
        Notification::assertSentTo($member->user, TeamMemberAccountCreated::class);
    }
}
