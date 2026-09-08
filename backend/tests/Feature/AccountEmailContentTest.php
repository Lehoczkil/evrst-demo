<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TeamMemberAccountCreated;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The temp-password email is the only onboarding instruction a member gets,
 * so it has to stand on its own: the sign-in address, the password, a link
 * to the panel, and the fact that @evrst.hu is a login rather than a mailbox.
 */
class AccountEmailContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_it_carries_everything_needed_to_sign_in(): void
    {
        $user = $this->rosterMember('kira.baba@evrst.hu', 'babakira@example.test');

        $mail = (new TeamMemberAccountCreated('Temp-Passw0rd!'))->toMail($user);
        $body = implode("\n", [...$mail->introLines, ...$mail->outroLines]);

        $this->assertStringContainsString('kira.baba@evrst.hu', $body, 'the sign-in address');
        $this->assertStringContainsString('Temp-Passw0rd!', $body, 'the temporary password');
        $this->assertStringContainsString(__('admin.mail.account_format'), $body, 'the address-format explainer');
        $this->assertStringContainsString(__('admin.mail.account_first_login'), $body, 'the forced-change warning');
        $this->assertStringContainsString('babakira@example.test', $body, 'where this mail was delivered');

        // Credentials come before the button, the explainers after it.
        $this->assertStringContainsString('kira.baba@evrst.hu', implode("\n", $mail->introLines));
        $this->assertStringContainsString(__('admin.mail.account_ignore'), implode("\n", $mail->outroLines));

        // A link to the panel, pointing at the admin login route.
        $this->assertNotEmpty($mail->actionText);
        $this->assertStringContainsString('/admin/login', $mail->actionUrl);
    }

    public function test_it_omits_the_split_explainer_when_login_and_inbox_match(): void
    {
        config(['mail.deliver_to_org_addresses' => true]);

        $user = $this->rosterMember('kira.baba@evrst.hu', null);

        $mail = (new TeamMemberAccountCreated('Temp-Passw0rd!'))->toMail($user);
        $body = implode("\n", [...$mail->introLines, ...$mail->outroLines]);

        $this->assertStringNotContainsString(
            __('admin.mail.account_split', ['delivery' => 'x', 'login' => 'y']),
            $body,
        );
    }

    public function test_the_login_link_survives_a_context_without_a_booted_panel(): void
    {
        config(['app.url' => 'https://admin.evrst.hu']);

        $user = $this->rosterMember('kira.baba@evrst.hu', 'babakira@example.test');

        $mail = (new TeamMemberAccountCreated('Temp-Passw0rd!'))->toMail($user);

        $this->assertStringContainsString('/admin/login', $mail->actionUrl);
        $this->assertStringStartsWith('http', $mail->actionUrl);
    }

    public function test_it_renders_in_the_locale_the_member_picked(): void
    {
        $hu = $this->rosterMember('kira.baba@evrst.hu', 'babakira@example.test');
        $hu->update(['locale' => 'hu']);

        $en = $this->rosterMember('marton.horvath@evrst.hu', 'marci@example.test');
        $en->update(['locale' => 'en']);

        Notification::sendNow($hu->fresh(), new TeamMemberAccountCreated('Temp-Passw0rd!'));
        Notification::sendNow($en->fresh(), new TeamMemberAccountCreated('Temp-Passw0rd!'));

        $subjects = Mail::mailer()->getSymfonyTransport()->messages()
            ->map(fn ($sent) => $sent->getOriginalMessage()->getSubject())
            ->all();

        $this->assertContains(__('admin.mail.account_subject', [], 'hu'), $subjects);
        $this->assertContains(__('admin.mail.account_subject', [], 'en'), $subjects);
    }

    public function test_hungarian_mail_translates_the_framework_chrome_too(): void
    {
        // Laravel's own notification layout strings ("Regards,", the
        // trouble-clicking subcopy) come from lang/hu.json, not admin.php.
        $this->assertSame('Üdvözlettel,', __('Regards,', [], 'hu'));
        $this->assertSame(
            'Jelszó-visszaállítás',
            __('Reset Password Notification', [], 'hu'),
        );
    }

    private function rosterMember(string $login, ?string $private): User
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
