<?php

namespace Tests\Feature;

use App\Auth\Perm;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locks down which roles can see which admin areas. Sponsors and
 * member applications must be invisible to Manager + Member; the
 * activity log + database inspector + role manager must be admin-only.
 */
class PermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
    }

    public function test_admin_has_every_permission(): void
    {
        $admin = $this->makeAdmin();

        foreach (Perm::adminPermissions() as $key) {
            $this->assertTrue($admin->can($key), "Admin missing permission: $key");
        }
    }

    public function test_manager_lacks_admin_only_permissions(): void
    {
        $manager = $this->makeManager();

        $this->assertFalse($manager->can(Perm::APPLICATIONS_ACCEPT));
        $this->assertFalse($manager->can(Perm::APPLICATIONS_REFUSE));
        $this->assertFalse($manager->can(Perm::SPONSORS_EDIT));
        $this->assertFalse($manager->can(Perm::NOTIFICATIONS_SEE));
    }

    public function test_member_is_read_only_on_visible_resources(): void
    {
        $member = $this->makeMember();

        $this->assertFalse($member->can(Perm::EVENTS_EDIT));
        $this->assertFalse($member->can(Perm::TASKS_EDIT));
        $this->assertFalse($member->can(Perm::SPONSORS_EDIT));
        $this->assertFalse($member->can(Perm::APPLICATIONS_ACCEPT));
    }

    /**
     * @dataProvider adminOnlyRoutesProvider
     */
    public function test_admin_only_routes_are_blocked_for_member(string $path): void
    {
        $member = $this->makeMember();

        $response = $this->actingAs($member)->get($path);

        // Filament can either 403 outright or redirect away when the
        // page's `canAccess` returns false. Either is acceptable —
        // a 200 means the member sees something they shouldn't.
        $this->assertContains(
            $response->status(),
            [302, 403, 404],
            "Member could access $path (status {$response->status()})",
        );
    }

    public static function adminOnlyRoutesProvider(): array
    {
        // Pages that strictly require Admin (or a perm only Admin
        // holds). Sponsors is omitted because Members are explicitly
        // allowed to view it as read-only.
        return [
            'roles'                => ['/admin/roles'],
            'activity-logs'        => ['/admin/activity-logs'],
            'database-inspector'   => ['/admin/database-inspector'],
            'member-applications'  => ['/admin/member-applications'],
            'users'                => ['/admin/users'],
        ];
    }

    /**
     * @dataProvider managerHiddenRoutesProvider
     */
    public function test_routes_hidden_from_manager_block_them(string $path): void
    {
        $manager = $this->makeManager();
        $response = $this->actingAs($manager)->get($path);

        $this->assertContains(
            $response->status(),
            [302, 403, 404],
            "Manager could access $path (status {$response->status()})",
        );
    }

    public static function managerHiddenRoutesProvider(): array
    {
        // Per the role catalogue: Manager has no sponsor / application /
        // notification permissions, and the resources guard against
        // them in `canViewAny`.
        return [
            'sponsors'            => ['/admin/cms/sponsors'],
            'member-applications' => ['/admin/member-applications'],
        ];
    }

    public function test_admin_can_access_admin_only_routes(): void
    {
        $admin = $this->makeAdmin();

        foreach (self::adminOnlyRoutesProvider() as [$path]) {
            $this->actingAs($admin)->get($path)->assertOk();
        }
    }

    /**
     * The counterpart to the list above: pages the whole team is meant to
     * reach. They are here so that "a member can open it" stays a decision
     * rather than an oversight — the inventory and the calendar were both
     * wide open by accident before, and only the calendar was supposed to
     * change.
     *
     * @dataProvider memberReadableRoutesProvider
     */
    public function test_member_readable_routes_stay_open(string $path): void
    {
        $member = $this->makeMember();

        $this->actingAs($member)->get($path)->assertOk();
    }

    public static function memberReadableRoutesProvider(): array
    {
        return [
            // Deliberately open to everyone: the whole team moves hardware
            // around, and every change is traced in the inventory log.
            'items'           => ['/admin/items'],
            'item-management' => ['/admin/item-management'],
            'item-log'        => ['/admin/item-log'],
            // Readable by all, writable by admins — see the calendar tests.
            'calendar'        => ['/admin/calendar'],
        ];
    }
}
