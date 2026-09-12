<?php

namespace Tests\Feature;

use App\Filament\Resources\MemberApplications\Pages\AcceptMemberApplication;
use App\Auth\Perm;
use App\Models\MemberApplication;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AcceptApplicationOrgLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        // Nothing in this flow may reach the network.
        Notification::fake();
        Bus::fake();
    }

    private function application(array $overrides = []): MemberApplication
    {
        return MemberApplication::create(array_merge([
            'id' => (string) Str::ulid(),
            'name' => 'Lehoczki László',
            'email' => 'lehoczkilaszlo2002@gmail.com',
            'status' => MemberApplication::STATUS_PENDING,
        ], $overrides));
    }

    public function test_the_accept_page_defaults_the_new_account_to_member(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->assertSet('data.role_id', Role::where('key', Perm::ROLE_MEMBER)->value('id'));
    }

    public function test_accepting_without_touching_the_role_creates_a_member(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'laszlo.lehoczki@evrst.hu')->firstOrFail();

        $this->assertSame(Perm::ROLE_MEMBER, $user->role->key);
    }

    /**
     * The whole point of the picker: the flow used to hardcode Member with
     * nothing on screen saying so, while the submit button read "admin
     * account" — so the one thing it could not do was what it claimed.
     */
    public function test_the_chosen_role_is_what_the_new_account_gets(): void
    {
        foreach ([Perm::ROLE_MANAGER, Perm::ROLE_ADMIN] as $index => $roleKey) {
            $application = $this->application([
                'id' => (string) Str::ulid(),
                'name' => "Teszt Ember{$index}",
                'email' => "teszt{$index}@example.test",
            ]);

            Livewire::actingAs($this->makeAdmin())
                ->test(AcceptMemberApplication::class, ['record' => $application->id])
                ->set('data.role_id', Role::where('key', $roleKey)->value('id'))
                ->call('save')
                ->assertHasNoFormErrors();

            $user = User::where('name', "Teszt Ember{$index}")->firstOrFail();

            $this->assertSame($roleKey, $user->role->key, "expected the {$roleKey} role");
            // Still a forced password change, whatever the role.
            $this->assertNull($user->password_changed_at);
        }
    }

    /**
     * A role id that isn't a real role is rejected before anything is
     * written — Filament's Select carries an `in:` rule over its options.
     * save() still falls back to Member if one ever gets past it, because
     * an account with no role signs in to a panel with nothing in it.
     */
    public function test_an_unknown_role_creates_nothing(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->set('data.role_id', 99999)
            ->call('save')
            ->assertHasFormErrors(['role_id']);

        $this->assertNull(User::where('email', 'laszlo.lehoczki@evrst.hu')->first());
        $this->assertSame(MemberApplication::STATUS_PENDING, $application->fresh()->status);
    }

    public function test_the_accept_page_prefills_a_generated_org_address(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->assertSet('data.email', 'laszlo.lehoczki@evrst.hu')
            ->assertSet('data.email_private', 'lehoczkilaszlo2002@gmail.com');
    }

    public function test_accepting_provisions_an_org_login_and_keeps_the_personal_inbox(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->call('save');

        $user = User::where('email', 'laszlo.lehoczki@evrst.hu')->firstOrFail();
        // Forced through the first-login password change.
        $this->assertNull($user->password_changed_at);

        $member = TeamMember::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('laszlo.lehoczki@evrst.hu', $member->email);
        $this->assertSame('lehoczkilaszlo2002@gmail.com', $member->email_private);

        // Delivery still goes to the inbox they can actually open.
        $this->assertSame('lehoczkilaszlo2002@gmail.com', $user->fresh('teamMember')->deliveryEmail());
        Notification::assertSentTo($user, TeamMemberAccountCreated::class);

        $this->assertSame(
            MemberApplication::STATUS_ACCEPTED,
            $application->fresh()->status,
        );
    }

    public function test_a_hand_edited_org_address_wins_over_the_generated_one(): void
    {
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->set('data.email', 'laci@evrst.hu')
            ->call('save');

        $this->assertTrue(User::where('email', 'laci@evrst.hu')->exists());
        $this->assertFalse(User::where('email', 'laszlo.lehoczki@evrst.hu')->exists());
    }

    public function test_a_taken_org_address_is_suffixed_at_prefill(): void
    {
        // A namesake already holds the derived address.
        $this->makeMember(['name' => 'Lehoczki László', 'email' => 'laszlo.lehoczki@evrst.hu']);
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->assertSet('data.email', 'laszlo.lehoczki.2@evrst.hu');
    }

    public function test_a_hand_entered_duplicate_is_rejected_before_provisioning(): void
    {
        $this->makeMember(['name' => 'Someone Else', 'email' => 'laci@evrst.hu']);
        $application = $this->application();

        Livewire::actingAs($this->makeAdmin())
            ->test(AcceptMemberApplication::class, ['record' => $application->id])
            ->set('data.email', 'laci@evrst.hu')
            ->call('save')
            ->assertHasErrors('data.email');

        $this->assertSame(
            MemberApplication::STATUS_PENDING,
            $application->fresh()->status,
        );
    }
}
