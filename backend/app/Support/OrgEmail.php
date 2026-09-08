<?php

namespace App\Support;

use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Builds the team's org email addresses (e.g. laszlo.lehoczki@evrst.hu)
 * from a roster name.
 *
 * The roster stores names in Hungarian order — "Lehoczki László" is
 * surname-first — while the address reads given-name-first, so the two
 * leading tokens are swapped. Accents are transliterated and everything
 * that isn't [a-z0-9] is dropped, which is what collapses hyphenated
 * surnames ("Tello-Pálfy Sebastián" → sebastian.tellopalfy).
 *
 * This is the single source of truth for the format: the seeder, the
 * backfill migration and the Accept-application form all call it, so a
 * change here can't leave the three out of step.
 */
class OrgEmail
{
    /**
     * The org mail domain. Configurable so a rename (or a test) doesn't
     * need a code change.
     */
    public static function domain(): string
    {
        return (string) config('mail.org_domain', 'evrst.hu');
    }

    /**
     * "Lehoczki László" → "laszlo.lehoczki@evrst.hu".
     *
     * Returns null when the name yields nothing usable (empty string,
     * emoji-only, etc.) so callers can fall back rather than mint a
     * nonsense login like ".@evrst.hu".
     */
    public static function forName(string $name, ?string $domain = null): ?string
    {
        $local = self::localPart($name);

        return $local === null ? null : $local . '@' . ($domain ?? self::domain());
    }

    /**
     * The part before the @, without the domain. Exposed for callers that
     * want to show a live preview next to a domain suffix.
     */
    public static function localPart(string $name): ?string
    {
        $tokens = preg_split('/\s+/', trim($name)) ?: [];

        $tokens = array_values(array_filter(array_map(
            static fn (string $token): string => self::slug($token),
            $tokens,
        ), static fn (string $token): bool => $token !== ''));

        if ($tokens === []) {
            return null;
        }

        // Single-token names have no surname to swap with.
        if (count($tokens) === 1) {
            return $tokens[0];
        }

        // Hungarian order in, given.surname out. Extra middle given names
        // ("Hernádi Andre Jozsef") are dropped — two components keep the
        // address short and predictable.
        [$surname, $given] = $tokens;

        return $given . '.' . $surname;
    }

    /**
     * Same as forName() but suffixed with a counter (.2, .3, …) until the
     * address is free across both users.email and team_members.email —
     * the two columns that carry a unique index on it.
     *
     * $ignoreUserId / $ignoreTeamMemberId let an existing row keep the
     * address it already owns instead of bumping itself to .2 on a re-run.
     */
    public static function uniqueForName(
        string $name,
        ?int $ignoreUserId = null,
        ?int $ignoreTeamMemberId = null,
    ): ?string {
        $local = self::localPart($name);
        if ($local === null) {
            return null;
        }

        $domain = self::domain();

        for ($n = 1; $n <= 50; $n++) {
            $candidate = ($n === 1 ? $local : $local . '.' . $n) . '@' . $domain;

            if (! self::isTaken($candidate, $ignoreUserId, $ignoreTeamMemberId)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * True when the address is already in use as a login or on another
     * team member row.
     */
    public static function isTaken(
        string $email,
        ?int $ignoreUserId = null,
        ?int $ignoreTeamMemberId = null,
    ): bool {
        $userTaken = User::where('email', $email)
            ->when($ignoreUserId !== null, fn ($q) => $q->whereKeyNot($ignoreUserId))
            ->exists();

        if ($userTaken) {
            return true;
        }

        return TeamMember::withTrashed()
            ->where('email', $email)
            ->when($ignoreTeamMemberId !== null, fn ($q) => $q->whereKeyNot($ignoreTeamMemberId))
            ->exists();
    }

    /** True for any address on the org domain. */
    public static function isOrgAddress(?string $email): bool
    {
        return is_string($email)
            && Str::endsWith(Str::lower($email), '@' . Str::lower(self::domain()));
    }

    private static function slug(string $token): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', Str::lower(Str::ascii($token)));
    }
}
