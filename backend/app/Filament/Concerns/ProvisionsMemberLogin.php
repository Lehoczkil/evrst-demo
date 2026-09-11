<?php

namespace App\Filament\Concerns;

use App\Filament\Support\MemberLoginReport;
use App\Models\TeamMember;
use App\Support\MemberLogin;
use App\Support\MemberLoginResult;

/**
 * Runs MemberLogin::provision() from a Filament page and turns the result
 * into a panel notification, so the automatic path (create page) and the
 * manual one ("Create login" on the edit page) always say the same thing.
 *
 * The wording itself lives in {@see MemberLoginReport}, because the team
 * members table has a "Create login" row action too and a table action's
 * closure has no access to a page trait's protected methods.
 */
trait ProvisionsMemberLogin
{
    protected function provisionMemberLogin(TeamMember $member): MemberLoginResult
    {
        $result = MemberLogin::provision($member);

        MemberLoginReport::flash($result, $member);

        return $result;
    }

    /**
     * Minting a panel account is an admin act — the "Create login" button
     * has always been admin-only, and team.create is a Manager permission,
     * so the automatic path must not become a way around that.
     */
    protected function canProvisionMemberLogin(): bool
    {
        return MemberLogin::canProvision();
    }
}
