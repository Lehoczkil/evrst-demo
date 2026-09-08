<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Break-glass role management. The Users resource is admin-only, so an
 * account that is not already an Admin cannot grant itself access through
 * the panel -- this command is the only way out of that corner.
 */
class UserRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_it_promotes_a_member_to_admin(): void
    {
        $user = $this->makeMember(['email' => 'laszlo.lehoczki@evrst.hu']);

        $exit = Artisan::call('user:role', ['email' => $user->email, 'role' => Perm::ROLE_ADMIN]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_it_reports_the_current_role_when_no_role_is_given(): void
    {
        $user = $this->makeManager(['email' => 'peter.czirjak@evrst.hu']);

        Artisan::call('user:role', ['email' => $user->email]);

        $this->assertStringContainsString('manager', Artisan::output());
        $this->assertSame(Perm::ROLE_MANAGER, $user->fresh()->role->key, 'a read must not change anything');
    }

    public function test_it_refuses_an_unknown_role(): void
    {
        $user = $this->makeMember();

        $exit = Artisan::call('user:role', ['email' => $user->email, 'role' => 'superuser']);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertSame(Perm::ROLE_MEMBER, $user->fresh()->role->key);
    }

    public function test_it_refuses_an_unknown_email(): void
    {
        $this->assertSame(
            Command::FAILURE,
            Artisan::call('user:role', ['email' => 'nobody@evrst.hu', 'role' => Perm::ROLE_ADMIN]),
        );
    }

    public function test_it_refuses_to_demote_the_last_admin(): void
    {
        $admin = $this->makeAdmin();

        $exit = Artisan::call('user:role', ['email' => $admin->email, 'role' => Perm::ROLE_MEMBER]);

        $this->assertSame(Command::FAILURE, $exit);
        $this->assertTrue($admin->fresh()->isAdmin(), 'the last admin must survive');
    }

    public function test_force_demotes_the_last_admin_anyway(): void
    {
        $admin = $this->makeAdmin();

        $exit = Artisan::call('user:role', [
            'email' => $admin->email,
            'role' => Perm::ROLE_MEMBER,
            '--force' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertFalse($admin->fresh()->isAdmin());
    }

    public function test_it_demotes_an_admin_when_another_one_remains(): void
    {
        $this->makeAdmin(['email' => 'other.admin@evrst.hu']);
        $admin = $this->makeAdmin(['email' => 'demote.me@evrst.hu']);

        $exit = Artisan::call('user:role', ['email' => $admin->email, 'role' => Perm::ROLE_MEMBER]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertFalse($admin->fresh()->isAdmin());
    }

    public function test_the_listing_fails_loudly_when_there_is_no_admin(): void
    {
        $this->makeMember(['email' => 'only.member@evrst.hu']);

        $exit = Artisan::call('user:role');
        $output = Artisan::output();

        $this->assertSame(Command::FAILURE, $exit, 'an adminless install is a broken install');
        $this->assertStringContainsString('No admin accounts', $output);
        $this->assertStringContainsString('user:role', $output, 'it must name the way out');
    }

    public function test_the_listing_succeeds_with_an_admin_present(): void
    {
        $this->makeAdmin();
        $this->makeMember();

        $this->assertSame(Command::SUCCESS, Artisan::call('user:role'));
    }

    public function test_setting_the_same_role_is_a_no_op(): void
    {
        $user = $this->makeAdmin();

        $exit = Artisan::call('user:role', ['email' => $user->email, 'role' => Perm::ROLE_ADMIN]);

        $this->assertSame(Command::SUCCESS, $exit);
        $this->assertStringContainsString('already', Artisan::output());
    }
}
