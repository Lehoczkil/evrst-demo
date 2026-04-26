<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CollectionSeeder::class);
    }

    public function test_admin_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_unauth_admin_redirects_to_login(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_authed_admin_can_open_dashboard(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_authed_admin_can_open_calendar(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/admin/calendar')->assertOk();
    }

    public function test_authed_admin_can_open_kanban(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/admin/tasks/kanban')->assertOk();
    }

    public function test_authed_admin_can_open_profile(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->get('/admin/profile')->assertOk();
    }

    public function test_member_application_post_creates_row(): void
    {
        $this->postJson('/api/member-applications', [
            'email' => 'smoke@example.com',
            'name' => 'Smoke Test',
            'department' => 'Propulsion',
            'why' => 'Smoke test',
            'languages' => ['English'],
        ])->assertCreated()->assertJsonStructure(['id']);

        $this->assertDatabaseHas('member_applications', [
            'email' => 'smoke@example.com',
            'status' => 'PENDING',
        ]);
    }

    public function test_member_application_post_validates(): void
    {
        $this->postJson('/api/member-applications', [
            'name' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['email', 'name']);
    }

    public function test_resource_api_returns_collection(): void
    {
        $this->getJson('/api/resource?collectionId=ced793f7-414b-41a7-8693-1e94627227df')
            ->assertOk();
    }

    private function makeAdmin(): User
    {
        $role = Role::where('key', 'admin')->firstOrFail();
        return User::create([
            'name' => 'Smoke Admin',
            'email' => 'smoke-admin@example.com',
            'password' => Hash::make('test1234'),
            'role_id' => $role->id,
            'password_changed_at' => now(),
        ]);
    }
}
