<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use App\Support\OrgEmail;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class OrgLoginEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_seeded_member_logs_in_with_their_org_address(): void
    {
        $this->seed(TeamSeeder::class);

        $laci = TeamMember::where('name', 'Lehoczki László')->firstOrFail();

        // The org address is both the login and the address on record...
        $this->assertSame('laszlo.lehoczki@evrst.hu', $laci->user->email);
        $this->assertSame('laszlo.lehoczki@evrst.hu', $laci->email);
        // ...while the personal inbox stays on the member row for delivery.
        $this->assertSame('lehoczkilaszlo2002@gmail.com', $laci->email_private);
    }

    public function test_every_seeded_login_is_a_unique_org_address(): void
    {
        $this->seed(TeamSeeder::class);

        $emails = TeamMember::with('user')->get()
            ->map(fn (TeamMember $m) => $m->user?->email)
            ->filter()
            ->values();

        // Derived from the roster rather than hardcoded: the point is that
        // every entry yields exactly one login, not what today's headcount
        // happens to be. The floor still catches a roster wiped by accident.
        $expected = count(self::rosterNames());
        $this->assertGreaterThan(10, $expected, 'the roster looks truncated');

        $this->assertCount($expected, $emails);
        // A roster name collision would otherwise surface as a unique-index
        // crash on the next reseed rather than as a failing assertion.
        $this->assertCount($expected, $emails->unique());

        foreach ($emails as $email) {
            $this->assertTrue(OrgEmail::isOrgAddress($email), $email . ' is not an org address');
        }
    }

    public function test_seeded_member_still_needs_a_first_login_password_change(): void
    {
        $this->seed(TeamSeeder::class);

        $member = TeamMember::where('name', 'Som Nemere')->firstOrFail();

        $this->assertSame('nemere.som@evrst.hu', $member->user->email);
        $this->assertNull($member->user->password_changed_at);
    }

    public function test_reseeding_moves_an_existing_login_instead_of_duplicating_it(): void
    {
        $this->seed(TeamSeeder::class);
        $this->seed(TeamSeeder::class);

        $this->assertSame(1, User::where('email', 'laszlo.lehoczki@evrst.hu')->count());
        $this->assertSame(count(self::rosterNames()), User::whereIn(
            'id',
            TeamMember::pluck('user_id')->filter()->all(),
        )->count());
    }

    /**
     * @return array<int, string>
     */
    private static function rosterNames(): array
    {
        $members = (new \ReflectionClass(TeamSeeder::class))->getConstant('MEMBERS');

        return array_column($members, 'name');
    }

    public function test_a_reset_requested_for_the_org_login_is_delivered_to_the_personal_inbox(): void
    {
        NotificationFacade::fake();

        $user = $this->makeMember(['name' => 'Lehoczki László', 'email' => 'laszlo.lehoczki@evrst.hu']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => 'laszlo.lehoczki@evrst.hu',
            'email_private' => 'lehoczkilaszlo2002@gmail.com',
        ]);

        // The member types their org address into "Forgot password?"...
        $status = Password::broker()->sendResetLink(['email' => 'laszlo.lehoczki@evrst.hu']);
        $this->assertSame(Password::RESET_LINK_SENT, $status);

        // ...and the link goes to the inbox they can actually open.
        NotificationFacade::assertSentTo(
            $user,
            ResetPassword::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routeNotificationForMail($notification)
                === ['lehoczkilaszlo2002@gmail.com' => 'Lehoczki László'],
        );
    }

    public function test_completed_password_reset_stamps_password_changed_at(): void
    {
        $user = User::factory()->create(['password_changed_at' => null]);

        event(new PasswordReset($user));

        $this->assertNotNull($user->fresh()->password_changed_at);
    }
}
