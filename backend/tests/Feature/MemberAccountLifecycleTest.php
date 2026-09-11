<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Filament\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\MemberLogin;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The two directions of the roster ↔ account link, as lifecycle rules
 * rather than as something a particular button happens to do:
 *
 *   member deleted → account deleted
 *   account deleted → member survives, unpublished, provisionable again
 *
 * Wired to model events, so these tests delete through the model rather
 * than through Filament — that is the contract, and the four delete paths
 * in the panel all reach it.
 */
class MemberAccountLifecycleTest extends TestCase
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

    private function user(string $email, string $roleKey = Perm::ROLE_MEMBER): User
    {
        return User::create([
            'name' => 'Test Person',
            'email' => $email,
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::where('key', $roleKey)->value('id'),
            'password_changed_at' => now(),
        ]);
    }

    private function member(?User $user = null, array $overrides = []): TeamMember
    {
        return TeamMember::create(array_merge([
            'name' => 'Pencz Máté',
            'email' => 'mate.pencz@evrst.hu',
            'email_private' => 'pencz.mate@example.test',
            'user_id' => $user?->getKey(),
            'is_public' => true,
        ], $overrides));
    }

    // ── member deleted → account deleted ────────────────────────────────

    public function test_deleting_a_team_member_deletes_their_login(): void
    {
        $user = $this->user('mate.pencz@evrst.hu');
        $member = $this->member($user);

        $member->delete();

        $this->assertNull(User::find($user->getKey()));
        $this->assertSoftDeleted('team_members', ['id' => $member->getKey()]);
    }

    public function test_it_keeps_your_own_account_when_you_delete_your_roster_row(): void
    {
        $me = $this->user('me@evrst.hu');
        $member = $this->member($me);

        $this->actingAs($me);
        $member->delete();

        $this->assertNotNull(User::find($me->getKey()));
    }

    public function test_it_keeps_an_admin_account_when_their_roster_row_goes(): void
    {
        $admin = $this->user('boss@evrst.hu', Perm::ROLE_ADMIN);
        $member = $this->member($admin);

        // Deleted by somebody else, so the self-deletion guard is not
        // what is doing the work here.
        $this->actingAs($this->user('other@evrst.hu'));
        $member->delete();

        $this->assertNotNull(User::find($admin->getKey()));
    }

    public function test_deleting_a_member_with_no_login_is_harmless(): void
    {
        $member = $this->member(null, ['email' => null]);

        $member->delete();

        $this->assertSoftDeleted('team_members', ['id' => $member->getKey()]);
    }

    // ── account deleted → member survives ───────────────────────────────

    public function test_deleting_a_user_keeps_the_team_member_but_unpublishes_them(): void
    {
        $user = $this->user('mate.pencz@evrst.hu');
        $member = $this->member($user);

        $user->delete();

        $member->refresh();
        $this->assertNull($member->deleted_at);
        $this->assertNull($member->user_id);
        $this->assertFalse($member->is_public);
    }

    public function test_an_unpublished_member_is_gone_from_the_public_api(): void
    {
        $user = $this->user('mate.pencz@evrst.hu');
        $member = $this->member($user);

        $this->getJson('/api/team/members')
            ->assertOk()
            ->assertJsonFragment(['id' => $member->getKey()]);

        $user->delete();

        $this->getJson('/api/team/members')
            ->assertOk()
            ->assertJsonMissing(['id' => $member->getKey()]);
    }

    public function test_a_login_can_be_provisioned_again_afterwards(): void
    {
        $user = $this->user('mate.pencz@evrst.hu');
        $member = $this->member($user);

        $user->delete();
        $member->refresh();

        $result = MemberLogin::provision($member);

        $this->assertNotNull($result->user);
        $this->assertSame('mate.pencz@evrst.hu', $result->user->email);

        $member->refresh();
        $this->assertSame($result->user->getKey(), $member->user_id);

        // Still off the public site: getting an account back is not the
        // same decision as going back on the roster page, and the admin
        // makes that one with the toggle.
        $this->assertFalse($member->is_public);
    }

    /**
     * The route back, as the admin actually walks it: delete the account
     * in Users, then hand the member a new one from the roster table
     * without opening the record.
     */
    public function test_the_table_row_action_can_hand_back_a_login(): void
    {
        $admin = $this->user('boss@evrst.hu', Perm::ROLE_ADMIN);
        $user = $this->user('mate.pencz@evrst.hu');
        $member = $this->member($user);

        $user->delete();
        $this->actingAs($admin);

        Livewire::test(ListTeamMembers::class)
            ->callTableAction('create_login', $member->fresh())
            ->assertHasNoTableActionErrors();

        $this->assertNotNull($member->fresh()->user_id);
    }

    public function test_the_row_action_is_hidden_once_a_member_has_a_login(): void
    {
        $admin = $this->user('boss@evrst.hu', Perm::ROLE_ADMIN);
        $member = $this->member($this->user('mate.pencz@evrst.hu'));

        $this->actingAs($admin);

        Livewire::test(ListTeamMembers::class)
            ->assertTableActionHidden('create_login', $member);
    }

    public function test_deleting_a_user_with_no_roster_row_is_harmless(): void
    {
        $user = $this->user('standalone@evrst.hu');

        $user->delete();

        $this->assertNull(User::find($user->getKey()));
    }
}
