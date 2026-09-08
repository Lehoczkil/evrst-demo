<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Catches the state that actually shipped to production: the team member
 * name was correct, but the login still carried a local part minted from
 * an older spelling. The name-keyed migration could not see it.
 */
class RealignOrgLoginsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_a_dry_run_reports_drift_without_writing(): void
    {
        [$user] = $this->drifted();

        $exit = Artisan::call('org-email:realign');
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('laszlo.lehoczki@evrst.hu', $output, 'it names the target address');
        $this->assertStringContainsString('would change', $output);
        $this->assertSame('laszlo.lehocki@evrst.hu', $user->fresh()->email, 'a dry run writes nothing');
    }

    public function test_apply_re_mints_both_rows(): void
    {
        [$user, $member] = $this->drifted();

        Artisan::call('org-email:realign', ['--apply' => true]);

        $this->assertSame('laszlo.lehoczki@evrst.hu', $user->fresh()->email);
        $this->assertSame('laszlo.lehoczki@evrst.hu', $member->fresh()->email);
    }

    public function test_it_leaves_an_address_its_owner_has_signed_in_with(): void
    {
        [$user] = $this->drifted(signedIn: true);

        $exit = Artisan::call('org-email:realign', ['--apply' => true]);
        $output = Artisan::output();

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertSame('laszlo.lehocki@evrst.hu', $user->fresh()->email, 'a used credential must not move');
        $this->assertStringContainsString('already signed in', $output);
    }

    public function test_include_signed_in_overrides_that(): void
    {
        [$user] = $this->drifted(signedIn: true);

        Artisan::call('org-email:realign', ['--apply' => true, '--include-signed-in' => true]);

        $this->assertSame('laszlo.lehoczki@evrst.hu', $user->fresh()->email);
    }

    public function test_a_collision_suffix_is_not_drift(): void
    {
        // `.2` was minted deliberately to break a clash; re-minting it
        // would fight the unique index it exists to satisfy.
        $user = $this->makeMember(['name' => 'Lehoczki László', 'email' => 'laszlo.lehoczki.2@evrst.hu']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehoczki László',
            'email' => 'laszlo.lehoczki.2@evrst.hu',
        ]);

        Artisan::call('org-email:realign', ['--apply' => true]);

        $this->assertSame('laszlo.lehoczki.2@evrst.hu', $user->fresh()->email);
    }

    public function test_it_ignores_a_non_org_address(): void
    {
        // Set deliberately (a break-glass account, an external contact) —
        // not this command's business.
        $user = $this->makeMember(['name' => 'Lehoczki László', 'email' => 'someone@gmail.com']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehoczki László',
            'email' => 'someone@gmail.com',
        ]);

        Artisan::call('org-email:realign', ['--apply' => true]);

        $this->assertSame('someone@gmail.com', $user->fresh()->email);
    }

    public function test_it_is_quiet_when_everything_matches(): void
    {
        $user = $this->makeMember(['name' => 'Bába Kíra', 'email' => 'kira.baba@evrst.hu']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Bába Kíra',
            'email' => 'kira.baba@evrst.hu',
        ]);

        Artisan::call('org-email:realign', ['--apply' => true]);

        $this->assertStringContainsString('matches its team member name', Artisan::output());
    }

    /**
     * @return array{0: User, 1: TeamMember}
     */
    private function drifted(bool $signedIn = false): array
    {
        $user = $this->makeMember([
            'name' => 'Lehoczki László',
            'email' => 'laszlo.lehocki@evrst.hu',
        ]);

        // makeUser()'s `?? now()` swallows a null override.
        $user->forceFill(['password_changed_at' => $signedIn ? now() : null])->save();

        $member = TeamMember::create([
            'user_id' => $user->id,
            'name' => 'Lehoczki László',
            'email' => 'laszlo.lehocki@evrst.hu',
            'email_private' => 'lehoczkilaszlo2002@gmail.com',
        ]);

        return [$user->fresh(), $member];
    }
}
