<?php

namespace App\Support;

use App\Models\TeamMember;

/**
 * Alumni status for a roster row.
 *
 * Backed by `team_members.left_at` rather than a column of its own,
 * because that column already *was* this feature — the public API filters
 * on it (`whereNull('left_at')`), the table filter is already labelled
 * "Alumni", and MemberLogin skips provisioning for a row that has it. A
 * second flag meaning the same thing would give two ways to be inactive
 * and two chances to set only one of them.
 *
 * What was missing is the act: marking someone alumni meant knowing that
 * an undated "Left at" field in the middle of a form was the switch.
 *
 * Deliberately does NOT touch the user account or its role. Alumni is a
 * statement about the roster, not about access: someone who has left may
 * still need to sign in to hand work over, and someone still on the team
 * may have no account at all.
 */
final class AlumniStatus
{
    public static function isAlumni(TeamMember $member): bool
    {
        return $member->left_at !== null;
    }

    /**
     * Mark the member as alumni, effective today unless a date is given.
     *
     * `is_public` is left alone on purpose: the public API already
     * excludes anyone with a `left_at`, so writing it here would be
     * redundant — and it would silently destroy a deliberate "hidden but
     * active" setting that could not be restored on the way back.
     */
    public static function mark(TeamMember $member, ?string $date = null): void
    {
        $member->forceFill(['left_at' => $date ?? now()->toDateString()])->save();
    }

    /** Put them back on the active roster. */
    public static function restore(TeamMember $member): void
    {
        $member->forceFill(['left_at' => null])->save();
    }

    public static function toggle(TeamMember $member): void
    {
        self::isAlumni($member) ? self::restore($member) : self::mark($member);
    }
}
