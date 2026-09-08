<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The Users table's bulk "send temp password" action — the onboarding path
 * for the whole roster at once. The interesting behaviour is who gets
 * skipped: rotating a password we cannot deliver locks the member out.
 */
class BulkTempPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    public function test_it_sends_to_members_with_a_private_address(): void
    {
        $user = $this->memberWithPrivateEmail('kira.baba@evrst.hu', 'babakira@example.test');

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('send_temp_password', [$user])
            ->assertHasNoTableActionErrors();

        Notification::assertSentTo($user, TeamMemberAccountCreated::class);
        $this->assertNull($user->fresh()->password_changed_at, 'a fresh temp password must re-arm the forced change');
    }

    public function test_it_skips_members_whose_org_address_has_no_mailbox(): void
    {
        // No email_private → deliveryEmail() falls back to the @evrst.hu
        // login, which is a sign-in name with nothing behind it.
        $user = $this->memberWithPrivateEmail('gyorgy.nyari@evrst.hu', null);
        $originalHash = $user->password;

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('send_temp_password', [$user]);

        Notification::assertNotSentTo($user, TeamMemberAccountCreated::class);

        $fresh = $user->fresh();
        $this->assertSame($originalHash, $fresh->password, 'a skipped member must keep their working password');
        $this->assertNotNull($fresh->password_changed_at, 'a skipped member must not be pushed back into the change gate');
    }

    public function test_it_sends_to_the_org_address_when_org_delivery_is_enabled(): void
    {
        config(['mail.deliver_to_org_addresses' => true]);

        $user = $this->memberWithPrivateEmail('gyorgy.nyari@evrst.hu', null);

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('send_temp_password', [$user]);

        Notification::assertSentTo($user, TeamMemberAccountCreated::class);
    }

    public function test_one_undeliverable_member_does_not_block_the_rest_of_the_batch(): void
    {
        $ok = $this->memberWithPrivateEmail('kira.baba@evrst.hu', 'babakira@example.test');
        $skipped = $this->memberWithPrivateEmail('nemere.som@evrst.hu', null);
        $alsoOk = $this->memberWithPrivateEmail('marton.horvath@evrst.hu', 'marci@example.test');

        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->callTableBulkAction('send_temp_password', [$ok, $skipped, $alsoOk]);

        Notification::assertSentTo($ok, TeamMemberAccountCreated::class);
        Notification::assertSentTo($alsoOk, TeamMemberAccountCreated::class);
        Notification::assertNotSentTo($skipped, TeamMemberAccountCreated::class);
    }

    public function test_the_action_is_a_visible_toolbar_button_not_a_dropdown_entry(): void
    {
        // It sits next to the per-row action and does something very
        // different. Hidden in the BulkActionGroup dropdown the two were
        // indistinguishable, and a real run mailed one person instead of 14.
        $this->actingAs($this->makeAdmin());

        Livewire::test(ListUsers::class)
            ->assertTableBulkActionExists('send_temp_password')
            ->assertTableBulkActionVisible('send_temp_password');
    }

    private function memberWithPrivateEmail(string $login, ?string $private): User
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
}
