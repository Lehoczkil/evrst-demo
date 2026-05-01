<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RouteNotificationForMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_returns_team_member_private_email_when_set(): void
    {
        $user = $this->makeMember(['name' => 'Routed', 'email' => 'fallback@example.test']);
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email_private' => 'private@example.test',
        ]);

        $this->assertSame(
            ['private@example.test' => 'Routed'],
            $user->fresh()->routeNotificationForMail(new \stdClass()),
        );
    }

    public function test_returns_account_email_when_no_team_member(): void
    {
        $user = $this->makeMember(['name' => 'Account Only', 'email' => 'account@example.test']);

        $this->assertSame(
            ['account@example.test' => 'Account Only'],
            $user->fresh()->routeNotificationForMail(new \stdClass()),
        );
    }

    public function test_returns_null_when_no_email_anywhere(): void
    {
        // makeUser/makeMember always sets an email; build a user with an
        // empty string directly so we can probe the null branch. We bypass
        // the mass-assignment pipeline because email is required there.
        $role = Role::where('key', 'member')->firstOrFail();
        $user = new User();
        $user->name = 'No Email';
        $user->email = '';
        $user->password = Hash::make('test1234');
        $user->role_id = $role->id;
        $user->password_changed_at = now();
        $user->save();

        $this->assertNull($user->fresh()->routeNotificationForMail(new \stdClass()));
    }
}
