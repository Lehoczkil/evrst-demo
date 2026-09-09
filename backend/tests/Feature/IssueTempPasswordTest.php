<?php

namespace Tests\Feature;

use App\Actions\IssueTempPassword;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The single path a temporary password leaves the app by. The behaviour
 * that used to differ between the five call sites: refuse an address we
 * cannot deliver to *before* rotating, roll back a rotation whose mail
 * failed, and report the address the mail actually went to.
 */
class IssueTempPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function member(string $login, ?string $private): User
    {
        $user = $this->makeMember(['name' => 'Roster Member', 'email' => $login]);

        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $login,
            'email_private' => $private,
        ]);

        return $user->fresh();
    }

    public function test_it_rotates_and_reports_the_private_address(): void
    {
        $user = $this->member('kira.baba@evrst.hu', 'babakira@example.test');
        $before = $user->password;

        $result = IssueTempPassword::rotate($user);

        $this->assertSame(IssueTempPassword::SENT, $result->status);
        // The reported address is where the mail went, not the login.
        $this->assertSame('babakira@example.test', $result->destination);
        $this->assertNotSame($before, $user->fresh()->password);
        $this->assertNull($user->fresh()->password_changed_at);
        Notification::assertSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_it_refuses_an_undeliverable_address_without_touching_the_account(): void
    {
        $user = $this->member('gyorgy.nyari@evrst.hu', null);
        $before = $user->password;

        $result = IssueTempPassword::rotate($user);

        $this->assertSame(IssueTempPassword::SKIPPED_UNDELIVERABLE, $result->status);
        $this->assertSame($before, $user->fresh()->password);
        $this->assertNotNull($user->fresh()->password_changed_at, 'a refused rotation must not re-arm the change gate');
        Notification::assertNothingSent();
    }

    public function test_a_failed_send_rolls_the_password_back(): void
    {
        $user = $this->member('kira.baba@evrst.hu', 'babakira@example.test');
        $before = $user->password;
        $changedAt = $user->password_changed_at;

        Notification::shouldReceive('sendNow')->once()->andThrow(new \RuntimeException('SMTP is down'));

        $result = IssueTempPassword::rotate($user);

        $this->assertSame(IssueTempPassword::FAILED, $result->status);
        $this->assertSame('SMTP is down', $result->error);

        $fresh = $user->fresh();
        $this->assertSame($before, $fresh->password, 'nobody knows the new password — the old one must survive');
        $this->assertEquals($changedAt, $fresh->password_changed_at);
    }

    public function test_the_row_action_skips_an_undeliverable_member(): void
    {
        $user = $this->member('nemere.som@evrst.hu', null);
        $before = $user->password;

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->callTableAction('resend_temp_password', $user)
            ->assertHasNoTableActionErrors();

        $this->assertSame($before, $user->fresh()->password);
        Notification::assertNotSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_create_user_mails_a_generated_password(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Új Tag',
                'email' => 'uj.tag@evrst.hu',
                'role_id' => Role::where('key', 'member')->value('id'),
                'password' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'uj.tag@evrst.hu')->firstOrFail();
        $this->assertNull($user->password_changed_at, 'a generated password must be replaced on first sign-in');
        // No team member row, so deliveryEmail() falls back to the org
        // login — undeliverable, hence nothing sent.
        Notification::assertNotSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_a_hand_typed_password_does_not_arm_the_forced_change(): void
    {
        $this->actingAs($this->makeAdmin());

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Kézi Jelszó',
                'email' => 'kezi.jelszo@example.test',
                'role_id' => Role::where('key', 'member')->value('id'),
                'password' => 'a-password-the-admin-handed-over',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'kezi.jelszo@example.test')->firstOrFail();
        $this->assertNotNull(
            $user->password_changed_at,
            'the admin typed this password and handed it over — no forced change',
        );
        Notification::assertNothingSent();
    }
}
