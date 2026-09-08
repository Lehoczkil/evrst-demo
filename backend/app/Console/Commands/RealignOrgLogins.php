<?php

namespace App\Console\Commands;

use App\Models\TeamMember;
use App\Support\OrgEmail;
use Illuminate\Console\Command;

/**
 * Find org logins that no longer match the name they were minted from,
 * and re-mint them.
 *
 * The one-shot 2026_09_08 migration keyed off a map of stale *names*,
 * which misses the case that actually shipped: the name was corrected
 * elsewhere (a reseed, a hand edit) while the address minted from the old
 * spelling stayed. Comparing the address against the current name catches
 * both, and needs no list of known typos.
 *
 * Dry-run by default -- an org address is a credential the moment someone
 * signs in with it, so changing one is never a side effect.
 */
class RealignOrgLogins extends Command
{
    protected $signature = 'org-email:realign
                            {--apply : Write the changes (default is a dry run)}
                            {--include-signed-in : Also move addresses of accounts that have signed in}';

    protected $description = 'Re-mint org logins that drifted from the team member name';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $includeSignedIn = (bool) $this->option('include-signed-in');

        $drifted = [];
        $skippedInUse = [];

        TeamMember::withTrashed()->with('user')->cursor()->each(
            function (TeamMember $member) use (&$drifted, &$skippedInUse, $includeSignedIn): void {
                $expected = OrgEmail::forName((string) $member->name);

                if ($expected === null || ! $this->hasDrifted($member->email, $expected)) {
                    return;
                }

                $user = $member->user;

                // Someone who has set their own password has used this
                // address to get in. Moving it silently would lock them out.
                if (! $includeSignedIn && $user && $user->password_changed_at !== null) {
                    $skippedInUse[] = [$member->name, (string) $member->email, $expected];

                    return;
                }

                $drifted[] = [$member, $expected];
            }
        );

        if ($skippedInUse !== []) {
            $this->components->warn('Drifted, but already signed in with the current address — left alone:');
            $this->table(['Name', 'Current login', 'Would become'], $skippedInUse);
            $this->line('  Move these deliberately from the team member page, or pass --include-signed-in.');
            $this->newLine();
        }

        if ($drifted === []) {
            // Don't claim everything matches when rows were listed as
            // skipped just above — that reads as "nothing was wrong" and
            // sends the reader looking for a different problem.
            if ($skippedInUse !== []) {
                $this->components->info(
                    'Nothing left to change. The drifted row(s) above were skipped, '
                    . 'not fixed — re-run with --include-signed-in to move them.'
                );

                return self::SUCCESS;
            }

            $this->components->info('Every org login matches its team member name.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($drifted as [$member, $expected]) {
            $target = $apply
                ? OrgEmail::uniqueForName(
                    (string) $member->name,
                    ignoreUserId: $member->user?->getKey(),
                    ignoreTeamMemberId: $member->getKey(),
                )
                : $expected;

            if ($target === null) {
                $rows[] = [$member->name, (string) $member->email, 'unusable name — skipped'];

                continue;
            }

            if ($apply) {
                $member->forceFill(['email' => $target])->saveQuietly();
                $member->user?->forceFill(['email' => $target])->saveQuietly();
            }

            $rows[] = [$member->name, (string) $member->email, $target];
        }

        $this->table(['Name', $apply ? 'Was' : 'Current login', $apply ? 'Now' : 'Would become'], $rows);

        if (! $apply) {
            $this->components->warn(count($rows) . ' login(s) would change. Re-run with --apply to write them.');

            return self::SUCCESS;
        }

        $this->components->info(count($rows) . ' login(s) re-minted.');

        return self::SUCCESS;
    }

    /**
     * A `.2` suffix is a deliberate collision break, not drift — so treat
     * `laszlo.lehoczki.2@evrst.hu` as matching `laszlo.lehoczki@evrst.hu`.
     */
    private function hasDrifted(?string $current, string $expected): bool
    {
        if ($current === null || $current === '') {
            return true;
        }

        if ($current === $expected) {
            return false;
        }

        // Only org addresses are ours to re-mint; anything else was set
        // deliberately and is none of this command's business.
        if (! OrgEmail::isOrgAddress($current)) {
            return false;
        }

        $expectedLocal = explode('@', $expected, 2)[0];
        $currentLocal = explode('@', $current, 2)[0];

        return ! preg_match('/^' . preg_quote($expectedLocal, '/') . '(\.\d+)?$/', $currentLocal);
    }
}
