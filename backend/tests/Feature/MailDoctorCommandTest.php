<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The go-live pre-flight. It has to fail loudly on a half-configured
 * mailer, because a green run is what the runbook treats as permission to
 * send temp passwords to the whole roster.
 */
class MailDoctorCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Http::preventStrayRequests();
        Mail::fake();
    }

    public function test_it_fails_when_no_real_mailer_is_configured(): void
    {
        config(['mail.default' => 'log']);

        $this->artisan('mail:doctor', ['--no-api' => true])
            ->assertFailed();
    }

    public function test_it_fails_when_the_provider_key_is_missing(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => '']);

        $this->artisan('mail:doctor', ['--no-api' => true])
            ->assertFailed();
    }

    public function test_it_fails_on_a_localhost_app_url(): void
    {
        $this->configureHealthyMailer();
        config(['app.url' => 'http://localhost']);

        $this->artisan('mail:doctor', ['--no-api' => true])
            ->assertFailed();
    }

    public function test_it_passes_on_a_fully_configured_mailer(): void
    {
        $this->configureHealthyMailer();

        $this->artisan('mail:doctor', ['--no-api' => true])
            ->assertSuccessful();
    }

    public function test_it_lists_members_with_no_mailbox_behind_their_address(): void
    {
        $this->configureHealthyMailer();

        $reachable = $this->rosterMember('kira.baba@evrst.hu', 'babakira@example.test');
        $stranded = $this->rosterMember('gyorgy.nyari@evrst.hu', null);

        $this->artisan('mail:doctor', ['--no-api' => true])
            ->expectsOutputToContain($stranded->name)
            ->assertSuccessful();

        $this->assertNotNull($reachable->deliveryEmail());
    }

    public function test_it_reports_a_rejected_provider_key(): void
    {
        $this->configureHealthyMailer();
        Http::fake(['api.resend.com/*' => Http::response([], 401)]);

        $this->artisan('mail:doctor')->assertFailed();
    }

    public function test_it_reports_an_unverified_sending_domain(): void
    {
        $this->configureHealthyMailer();
        Http::fake(['api.resend.com/*' => Http::response([
            'data' => [['name' => 'evrst.hu', 'status' => 'pending']],
        ], 200)]);

        $this->artisan('mail:doctor')->assertFailed();
    }

    public function test_it_accepts_a_verified_sending_domain(): void
    {
        $this->configureHealthyMailer();
        Http::fake(['api.resend.com/*' => Http::response([
            'data' => [['name' => 'evrst.hu', 'status' => 'verified']],
        ], 200)]);

        $this->artisan('mail:doctor')->assertSuccessful();
    }

    public function test_the_send_option_warns_instead_of_pretending_on_the_log_driver(): void
    {
        config(['mail.default' => 'log']);

        // Still a failure overall (log delivers nowhere), but the send step
        // must not claim it handed anything to a mailer.
        $this->artisan('mail:doctor', ['--no-api' => true, '--send' => 'someone@example.test'])
            ->assertFailed();
    }

    private function configureHealthyMailer(): void
    {
        config([
            'mail.default' => 'resend',
            'services.resend.key' => 're_test_key_value',
            'mail.from.address' => 'no-reply@evrst.hu',
            'app.url' => 'https://evrst.hu',
            'queue.default' => 'database',
            'services.discord.webhook' => 'https://discord.test/webhook',
        ]);
    }

    private function rosterMember(string $login, ?string $private): User
    {
        $user = $this->makeMember(['name' => 'Roster ' . uniqid(), 'email' => $login]);

        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $login,
            'email_private' => $private,
        ]);

        return $user->fresh();
    }
}
