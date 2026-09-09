<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * `team:provision-login` — the shell path for roster rows that predate
 * automatic provisioning on the Team members create page.
 */
class ProvisionMemberLoginCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    private function member(array $overrides = []): TeamMember
    {
        return TeamMember::create(array_merge([
            'name' => 'Pencz Máté',
            'email_private' => 'pencz.mate@example.test',
        ], $overrides));
    }

    public function test_it_provisions_a_login_matched_by_name(): void
    {
        $member = $this->member();

        $this->artisan('team:provision-login', ['member' => 'Pencz'])
            ->assertExitCode(0);

        $member->refresh();
        $this->assertSame('mate.pencz@evrst.hu', $member->email);
        $this->assertNotNull($member->user_id);
        Notification::assertSentTo($member->user, TeamMemberAccountCreated::class);
    }

    public function test_it_is_idempotent(): void
    {
        $member = $this->member(['email' => 'mate.pencz@evrst.hu']);

        $this->artisan('team:provision-login', ['member' => (string) $member->id]);
        $first = $member->fresh()->user_id;

        $this->artisan('team:provision-login', ['member' => (string) $member->id])
            ->expectsOutputToContain('nothing to do')
            ->assertExitCode(0);

        $this->assertSame($first, $member->fresh()->user_id);
        $this->assertSame(1, User::where('email', 'mate.pencz@evrst.hu')->count());
    }

    public function test_an_ambiguous_name_refuses_rather_than_guessing(): void
    {
        $this->member();
        $this->member(['name' => 'Pencz Máté Bence']);

        $this->artisan('team:provision-login', ['member' => 'Pencz'])
            ->assertExitCode(1);

        $this->assertSame(0, User::where('email', 'like', '%@evrst.hu')->count());
    }

    public function test_no_argument_lists_the_rows_that_need_one(): void
    {
        $this->member();

        $this->artisan('team:provision-login')
            ->expectsOutputToContain('Pencz Máté')
            ->assertExitCode(0);

        $this->assertSame(0, User::count());
    }
}
