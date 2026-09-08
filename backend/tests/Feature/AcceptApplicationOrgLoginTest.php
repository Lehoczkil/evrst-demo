<?php

namespace Tests\Feature;

use App\Filament\Resources\MemberApplications\Pages\AcceptMemberApplication;
use App\Models\MemberApplication;
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
