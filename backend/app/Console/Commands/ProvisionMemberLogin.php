<?php

namespace App\Console\Commands;

use App\Models\TeamMember;
use App\Support\MemberLogin;
use App\Support\MemberLoginResult;
use Illuminate\Console\Command;

/**
 * Give a roster row the panel login it is missing.
 *
 * The Team members page provisions one automatically on create, but rows
 * that predate that (or were imported by the seeder) still need a login
 * minted by hand. This is that path for a box with no browser open —
 * `docker compose exec backend php artisan team:provision-login "Pencz Máté"`.
 */
class ProvisionMemberLogin extends Command
{
    protected $signature = 'team:provision-login
                            {member? : Team member id, org address or (part of a) name; omit to list rows with no login}
                            {--all : Provision every row that has no login}';

    protected $description = 'Create the Member-role login for a team member and email the temp password';

    public function handle(): int
    {
        $needsLogin = TeamMember::whereNull('user_id')->orderBy('name');

        if ($this->option('all')) {
            return $this->provisionAll($needsLogin->get());
        }

        $needle = $this->argument('member');

        if ($needle === null) {
            return $this->listMissing($needsLogin->get());
        }

        $member = $this->resolve((string) $needle);

        if (! $member instanceof TeamMember) {
            return $member;
        }

        $this->report($member, MemberLogin::provision($member));

        return self::SUCCESS;
    }

    /** @return TeamMember|int the member, or an exit code when it couldn't be resolved */
    private function resolve(string $needle): TeamMember|int
    {
        $matches = TeamMember::query()
            ->when(ctype_digit($needle), fn ($q) => $q->orWhere('id', (int) $needle))
            ->orWhere('email', $needle)
            ->orWhere('email_private', $needle)
            ->orWhere('name', 'like', '%' . $needle . '%')
            ->orderBy('name')
            ->get();

        if ($matches->isEmpty()) {
            $this->components->error("No team member matches '{$needle}'.");
            $this->line('  Run `php artisan team:provision-login` with no arguments to list rows without a login.');

            return self::FAILURE;
        }

        if ($matches->count() > 1) {
            $this->components->error("'{$needle}' matches " . $matches->count() . ' team members.');
            $this->table(['ID', 'Name', 'Org address'], $matches->map(
                fn (TeamMember $m) => [$m->id, $m->name, $m->email ?? '—'],
            )->all());
            $this->line('  Re-run with the id.');

            return self::FAILURE;
        }

        return $matches->first();
    }

    /** @param  \Illuminate\Database\Eloquent\Collection<int, TeamMember>  $members */
    private function listMissing($members): int
    {
        if ($members->isEmpty()) {
            $this->components->info('Every team member already has a login.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Org address', 'Private email'],
            $members->map(fn (TeamMember $m) => [
                $m->id,
                $m->name,
                $m->email ?? '— (will be derived)',
                $m->email_private ?? '— (temp password cannot be delivered)',
            ])->all(),
        );

        $this->line('  Provision one with: php artisan team:provision-login <id>');

        return self::SUCCESS;
    }

    /** @param  \Illuminate\Database\Eloquent\Collection<int, TeamMember>  $members */
    private function provisionAll($members): int
    {
        if ($members->isEmpty()) {
            $this->components->info('Every team member already has a login.');

            return self::SUCCESS;
        }

        // Each one mails a password to a real person — never on a stray flag.
        if (! $this->confirm('Provision logins for ' . $members->count() . ' team member(s) and email each a temporary password?')) {
            return self::FAILURE;
        }

        foreach ($members as $member) {
            $this->report($member, MemberLogin::provision($member));
        }

        return self::SUCCESS;
    }

    private function report(TeamMember $member, MemberLoginResult $result): void
    {
        $login = $result->user?->email ?? $member->email ?? '—';

        match ($result->status) {
            MemberLogin::CREATED_SENT => $this->components->info(
                "{$member->name}: login {$login} created, temp password sent to {$result->destination}."
                . (config('mail.default') === 'log' ? ' (MAIL_MAILER=log — it went to storage/logs/laravel.log, not to them.)' : ''),
            ),
            MemberLogin::CREATED_UNDELIVERABLE => $this->components->warn(
                "{$member->name}: login {$login} created, but no deliverable address is on file — no mail was sent."
                . ' Add a private email, then run `php artisan team:provision-login` again or use Resend temp password.',
            ),
            MemberLogin::CREATED_MAIL_FAILED => $this->components->error(
                "{$member->name}: login {$login} created, but the email failed: {$result->error}",
            ),
            MemberLogin::LINKED_EXISTING => $this->components->info(
                "{$member->name}: an account already used {$login} — linked it. No password was changed.",
            ),
            MemberLogin::ALREADY_LINKED => $this->components->info(
                "{$member->name} already signs in as {$login} — nothing to do.",
            ),
            MemberLogin::NO_ADDRESS => $this->components->error(
                "{$member->name}: no org address on the row and none derivable from the name. Set team_members.email first.",
            ),
            default => null,
        };
    }
}
