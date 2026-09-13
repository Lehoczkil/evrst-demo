<?php

namespace Tests\Feature;

use App\Filament\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Account takeover through the roster.
 *
 * `TeamMemberResource::canEdit()` asks only for `team.edit`, which is a
 * Manager permission, and the form used to expose `email` (the login),
 * `email_private` (where User::deliveryEmail() sends password-reset mail)
 * and `user_id` (which account the row belongs to) with no role gate. A
 * manager could open an admin's roster row, point `email_private` at
 * their own inbox, and use "Forgot password?" to walk into the admin
 * account — or rewrite `users.email` outright through
 * EditTeamMember::syncLoginEmail().
 *
 * Every assertion here sets the Livewire property directly rather than
 * clicking a field, because a disabled input is a rendering hint and the
 * payload of /livewire/update is attacker-controlled.
 */
class RosterIdentityGuardTest extends TestCase
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

    /** An admin with a roster row — the takeover target. */
    private function victim(): TeamMember
    {
        $user = $this->makeAdmin(['name' => 'Admin Anna', 'email' => 'anna.admin@evrst.hu']);

        return TeamMember::create([
            'user_id' => $user->getKey(),
            'name' => 'Admin Anna',
            'email' => 'anna.admin@evrst.hu',
            'email_private' => 'anna@example.test',
            'is_public' => true,
            'position' => 0,
        ]);
    }

    public function test_a_manager_cannot_redirect_an_admins_password_mail(): void
    {
        $member = $this->victim();

        Livewire::actingAs($this->makeManager())
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->set('data.email_private', 'attacker@example.test')
            ->call('save');

        $this->assertSame('anna@example.test', $member->fresh()->email_private);
    }

    public function test_a_manager_cannot_rewrite_an_admins_login_address(): void
    {
        $member = $this->victim();

        Livewire::actingAs($this->makeManager())
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->set('data.email', 'attacker@evrst.hu')
            ->call('save');

        $this->assertSame('anna.admin@evrst.hu', $member->fresh()->email);
        $this->assertSame('anna.admin@evrst.hu', $member->fresh()->user->email);
        $this->assertNull(User::where('email', 'attacker@evrst.hu')->first());
    }

    public function test_a_manager_cannot_repoint_a_roster_row_at_another_account(): void
    {
        $member = $this->victim();
        $manager = $this->makeManager();

        Livewire::actingAs($manager)
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->set('data.user_id', $manager->getKey())
            ->call('save');

        $this->assertSame($member->user_id, $member->fresh()->user_id);
    }

    /** The manager keeps every non-identity field they always had. */
    public function test_a_manager_can_still_edit_the_rest_of_the_row(): void
    {
        $member = $this->victim();

        Livewire::actingAs($this->makeManager())
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->set('data.discord_nick', 'anna_nick')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('anna_nick', $member->fresh()->discord_nick);
    }

    /** A manager adding a roster row cannot seed a delivery address either. */
    public function test_a_manager_cannot_plant_a_private_address_on_create(): void
    {
        Livewire::actingAs($this->makeManager())
            ->test(CreateTeamMember::class)
            ->set('data.name', 'Uj Tag')
            ->set('data.email_private', 'attacker@example.test')
            ->call('create');

        $created = TeamMember::where('name', 'Uj Tag')->first();

        $this->assertNotNull($created);
        $this->assertNull($created->email_private);
    }

    public function test_an_admin_still_owns_all_three_fields(): void
    {
        $member = $this->victim();
        $spare = $this->makeAdmin(['name' => 'Spare', 'email' => 'spare@evrst.hu']);

        Livewire::actingAs($this->makeAdmin())
            ->test(EditTeamMember::class, ['record' => $member->getKey()])
            ->set('data.email_private', 'anna.new@example.test')
            ->set('data.user_id', $spare->getKey())
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $member->fresh();

        $this->assertSame('anna.new@example.test', $fresh->email_private);
        $this->assertSame($spare->getKey(), $fresh->user_id);
    }
}
