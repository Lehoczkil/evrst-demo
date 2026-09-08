<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\OrgEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

/**
 * Pre-flight for the outbound mail path: is a real mailer configured, does
 * the provider accept the key, and is every member reachable.
 *
 * Exists so the go-live checks are one short command instead of several
 * multi-line one-liners pasted into a remote console.
 *
 * Read-only unless --send is passed, which delivers one plain test message.
 */
class MailDoctor extends Command
{
    protected $signature = 'mail:doctor
                            {--send= : Deliver a plain test message to this address}
                            {--no-api : Skip the provider API round-trip}';

    protected $description = 'Check the mail configuration, the provider key, and who is unreachable';

    private bool $failed = false;

    public function handle(): int
    {
        $this->newLine();
        $this->components->info('Mail configuration');
        $this->configTable();

        if (! $this->option('no-api')) {
            $this->newLine();
            $this->components->info('Provider');
            $this->checkProvider();
        }

        $this->newLine();
        $this->components->info('Recipients');
        $this->checkRecipients();

        if ($address = $this->option('send')) {
            $this->newLine();
            $this->components->info('Test message');
            $this->sendTest($address);
        }

        $this->newLine();

        if ($this->failed) {
            $this->components->error('Not ready to send. Fix the ✗ rows above.');

            return self::FAILURE;
        }

        $this->components->info('Mail path looks healthy.');

        return self::SUCCESS;
    }

    private function configTable(): void
    {
        $mailer = (string) config('mail.default');
        $from = (string) config('mail.from.address');
        $appUrl = (string) config('app.url');

        $rows = [];

        // `log` and `array` write nowhere a member can read.
        $rows[] = $this->row(
            'MAIL_MAILER',
            $mailer,
            ! in_array($mailer, ['log', 'array'], true),
            'nothing is delivered on this driver — set MAIL_MAILER=resend',
        );

        // Show the provider key even on the log driver. Otherwise "the key
        // is set but MAIL_MAILER was never switched" looks identical to
        // "there is no key" — and that is the single most likely go-live
        // mistake, since the two live on separate lines of the same file.
        $provider = in_array($mailer, ['resend', 'postmark'], true) ? $mailer : 'resend';
        $providerKey = (string) config('services.' . $provider . '.key');

        // Never print the key itself.
        $rows[] = $this->row(
            strtoupper($provider) . '_API_KEY',
            $providerKey === '' ? 'empty' : sprintf('set (%d chars, %s…)', strlen($providerKey), substr($providerKey, 0, 3)),
            $providerKey !== '',
            // On log/array the driver row already failed the run; don't
            // double-report, just say what it means.
            in_array($mailer, ['log', 'array'], true)
                ? ''
                : 'the driver cannot authenticate without it',
        );

        $rows[] = $this->row(
            'MAIL_FROM_ADDRESS',
            $from === '' ? 'empty' : $from,
            $from !== '' && ! str_ends_with($from, '@example.com'),
            'must be an address on a domain verified with the provider',
        );

        // Queued notifications render with no request in scope, so every
        // link in them is built from this value.
        $rows[] = $this->row(
            'APP_URL',
            $appUrl,
            $appUrl !== '' && ! str_contains($appUrl, 'localhost') && ! str_contains($appUrl, '127.0.0.1'),
            'queued notification emails build their links from this',
        );

        $rows[] = $this->row(
            'QUEUE_CONNECTION',
            (string) config('queue.default'),
            config('queue.default') !== 'null',
            'task notifications are queued and would be dropped',
        );

        $rows[] = $this->row(
            'MAIL_DELIVER_TO_ORG',
            config('mail.deliver_to_org_addresses') ? 'true' : 'false',
            true,
            '',
        );

        // Discord fails open: an empty webhook silently posts nothing.
        $rows[] = $this->row(
            'DISCORD_WEBHOOK_URL',
            config('services.discord.webhook') ? 'set' : 'empty',
            (bool) config('services.discord.webhook'),
            'Discord posts are silently skipped while this is empty',
        );

        $this->table(['Setting', 'Value', '', 'Note'], $rows);
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string}
     */
    private function row(string $label, string $value, bool $ok, string $note): array
    {
        if (! $ok && $note !== '') {
            $this->failed = true;
        }

        return [$label, $value, $ok ? '<fg=green>✓</>' : '<fg=red>✗</>', $ok ? '' : $note];
    }

    /**
     * Ask the provider whether the key works and the sending domain is
     * verified. A 401 here is the difference between "configured" and
     * "actually able to send".
     */
    private function checkProvider(): void
    {
        $mailer = (string) config('mail.default');

        if ($mailer !== 'resend') {
            $this->components->warn("No API check implemented for the '{$mailer}' driver — skipping.");

            return;
        }

        $key = (string) config('services.resend.key');

        if ($key === '') {
            $this->components->error('No Resend key to check.');
            $this->failed = true;

            return;
        }

        try {
            $response = Http::withToken($key)->timeout(10)->get('https://api.resend.com/domains');
        } catch (\Throwable $e) {
            $this->components->error('Could not reach api.resend.com: ' . $e->getMessage());
            $this->failed = true;

            return;
        }

        if ($response->status() === 401) {
            $this->components->error('Resend rejected the key (HTTP 401). It is wrong, revoked, or from another account.');
            $this->failed = true;

            return;
        }

        if (! $response->successful()) {
            $this->components->error('Resend returned HTTP ' . $response->status() . '.');
            $this->failed = true;

            return;
        }

        $this->components->info('Key accepted (HTTP 200).');

        $domains = $response->json('data') ?? [];
        $fromDomain = substr(strrchr((string) config('mail.from.address'), '@') ?: '', 1);

        if ($domains === []) {
            $this->components->warn('No domains registered on this Resend account — sending will be rejected.');
            $this->failed = true;

            return;
        }

        $rows = [];
        $fromVerified = false;

        foreach ($domains as $domain) {
            $name = $domain['name'] ?? '?';
            $status = $domain['status'] ?? '?';
            $isFrom = $name === $fromDomain;

            if ($isFrom && $status === 'verified') {
                $fromVerified = true;
            }

            $rows[] = [$name, $status, $isFrom ? '← MAIL_FROM_ADDRESS' : ''];
        }

        $this->table(['Domain', 'Status', ''], $rows);

        if (! $fromVerified) {
            $this->components->error("'{$fromDomain}' is not a verified domain here — Resend will reject the From address.");
            $this->failed = true;
        }
    }

    /**
     * Anyone whose delivery address resolves to an org login has no mailbox
     * behind it while MAIL_DELIVER_TO_ORG is off — mail to them bounces,
     * and the bulk send-temp-password action skips them.
     */
    private function checkRecipients(): void
    {
        $unreachable = [];
        $total = 0;

        User::with('teamMember')->orderBy('name')->cursor()->each(
            function (User $user) use (&$unreachable, &$total): void {
                $total++;
                $destination = $user->deliveryEmail();

                $deliverable = $destination !== null
                    && ((bool) config('mail.deliver_to_org_addresses') || ! OrgEmail::isOrgAddress($destination));

                if (! $deliverable) {
                    $unreachable[] = [$user->name, $user->email, $destination ?? '—'];
                }
            }
        );

        $reachable = $total - count($unreachable);
        $this->line("  {$reachable} of {$total} accounts have a deliverable address.");

        if ($unreachable !== []) {
            $this->newLine();
            $this->table(['Name', 'Login', 'Resolves to'], $unreachable);
            $this->components->warn(
                'These have no mailbox behind their address. Add a private email '
                . '(Membership → Team members) or hand the password over out of band. '
                . 'The bulk action skips them rather than rotating a password it cannot deliver.'
            );
        }
    }

    private function sendTest(string $address): void
    {
        try {
            Mail::raw(
                "EVRST mail test.\n\nIf you are reading this, the outbound mail path works.",
                fn ($message) => $message->to($address)->subject('[EVRST] mail test'),
            );
        } catch (\Throwable $e) {
            $this->components->error('Send failed: ' . $e->getMessage());
            $this->failed = true;

            return;
        }

        if (in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->components->warn(
                'Driver is ' . config('mail.default') . ' — written to the log, NOT delivered.'
            );

            return;
        }

        $this->components->info("Handed to the mailer for {$address}. Confirm delivery in the provider's logs.");
    }
}
