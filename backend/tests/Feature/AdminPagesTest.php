<?php

namespace Tests\Feature;

use App\Models\BugReport;
use App\Models\CalendarEvent;
use App\Models\Cms\Event;
use App\Models\Collection;
use App\Models\MemberApplication;
use App\Models\Resource;
use App\Models\Task;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Smoke-tests every admin page returns 200 OK for an authenticated
 * Admin. Records get created per-test as needed so the
 * `/{record}/edit` routes have something to load. If any of these go
 * red the panel is either misrouted or a resource form is throwing
 * during boot.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
    }

    public function test_login_page_renders_for_guests(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_unauthenticated_admin_redirects_to_login(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_dashboard_renders(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertOk();
    }

    /**
     * @dataProvider customPagesProvider
     */
    public function test_custom_admin_pages_render(string $path): void
    {
        $this->actingAs($this->makeAdmin())->get($path)->assertOk();
    }

    public static function customPagesProvider(): array
    {
        return [
            'calendar'           => ['/admin/calendar'],
            'about-content'      => ['/admin/about-content'],
            'database-inspector' => ['/admin/database-inspector'],
            'kanban'             => ['/admin/tasks/kanban'],
            'profile'            => ['/admin/profile'],
            'drawing-studio'     => ['/admin/drawings/draw'],
        ];
    }

    /**
     * @dataProvider listPagesProvider
     */
    public function test_resource_index_pages_render(string $path): void
    {
        $this->actingAs($this->makeAdmin())->get($path)->assertOk();
    }

    /**
     * @dataProvider listPagesProvider
     */
    public function test_resource_create_pages_render(string $path): void
    {
        // Resources without a `create` page (read-only listings,
        // custom flows, etc.) — skip them so the data provider can
        // stay one source of truth for index pages too.
        $noCreate = [
            '/admin/roles',                // Edit-only.
            '/admin/activity-logs',        // Auto-written, read-only.
            '/admin/drawings',             // Uses /draw studio, not /create.
            '/admin/member-applications',  // Created via the public API.
        ];
        if (in_array($path, $noCreate, true)) {
            $this->markTestSkipped("$path has no /create page.");
        }

        $this->actingAs($this->makeAdmin())->get($path . '/create')->assertOk();
    }

    public static function listPagesProvider(): array
    {
        return [
            'activity-logs'        => ['/admin/activity-logs'],
            'bug-reports'          => ['/admin/bug-reports'],
            'collections'          => ['/admin/collections'],
            'drawings'             => ['/admin/drawings'],
            'member-applications'  => ['/admin/member-applications'],
            'onshape-models'       => ['/admin/onshape-models'],
            'resources'            => ['/admin/resources'],
            'roles'                => ['/admin/roles'],
            'tasks'                => ['/admin/tasks'],
            'users'                => ['/admin/users'],
            'cms-events'           => ['/admin/cms/events'],
            'cms-sponsors'         => ['/admin/cms/sponsors'],
            'cms-mentors'          => ['/admin/cms/mentors'],
            'cms-about-goals'      => ['/admin/cms/about-goals'],
            'cms-about-projects'   => ['/admin/cms/about-projects'],
            'cms-team-members'     => ['/admin/cms/team-members'],
            'cms-team-member-grps' => ['/admin/cms/team-member-groups'],
        ];
    }

    public function test_task_edit_page_renders(): void
    {
        $admin = $this->makeAdmin();
        $task = Task::create([
            'title' => 'Smoke task',
            'description' => 'Smoke description for the test that must be at least ten characters long.',
            'status' => 'TODO',
            'supervisor_id' => $admin->id,
            'created_by' => $admin->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/admin/tasks/' . $task->id . '/edit')
            ->assertOk();
    }

    public function test_bug_report_edit_page_renders(): void
    {
        $admin = $this->makeAdmin();
        $bug = BugReport::create([
            'reporter_id' => $admin->id,
            'title' => 'Smoke bug',
            'description' => 'Reproducible smoke bug',
            'status' => BugReport::STATUS_OPEN,
            'severity' => BugReport::SEVERITY_LOW,
        ]);

        $this->actingAs($admin)
            ->get('/admin/bug-reports/' . $bug->id . '/edit')
            ->assertOk();
    }

    public function test_calendar_renders_with_seeded_event(): void
    {
        $admin = $this->makeAdmin();
        CalendarEvent::create([
            'user_id' => $admin->id,
            'title' => 'Smoke calendar event',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'all_day' => false,
            'color' => '#0ea5e9',
        ]);

        $this->actingAs($admin)->get('/admin/calendar')->assertOk();
    }

    public function test_application_edit_page_renders_for_pending_row(): void
    {
        $admin = $this->makeAdmin();
        $app = MemberApplication::create([
            'email' => 'pending@example.test',
            'name' => 'Pending Person',
            'department' => 'Avionics',
            'status' => MemberApplication::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get('/admin/member-applications/' . $app->id . '/edit')
            ->assertOk();
    }

    public function test_resource_edit_page_renders(): void
    {
        $admin = $this->makeAdmin();
        // CollectionSeeder seeds an "events" collection — reuse it so
        // we don't have to know the exact `slug` constraint shape.
        $collection = Collection::query()->firstOrFail();
        $resource = Resource::create([
            'collection_id' => $collection->id,
            'payload' => ['title' => ['en' => 'Smoke', 'hu' => 'Smoke']],
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/admin/resources/' . $resource->id . '/edit')
            ->assertOk();
    }

    public function test_event_edit_page_renders(): void
    {
        // Cms\Event is a scoped Resource model — pull the seeded
        // "events" collection by slug so the test is independent of
        // the underlying ID.
        $admin = $this->makeAdmin();
        $collection = Collection::where('slug', 'events')->firstOrFail();
        $event = Event::create([
            'collection_id' => $collection->id,
            'payload' => [
                'title' => ['en' => 'Smoke event', 'hu' => 'Smoke event'],
                'status' => 'DRAFT',
                'start_at' => now()->addWeek()->toIso8601String(),
            ],
            'position' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/admin/cms/events/' . $event->id . '/edit')
            ->assertOk();
    }

    public function test_logout_returns_a_redirect(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/logout')
            ->assertRedirect();
    }
    public function test_the_application_form_editor_renders(): void
    {
        $admin = $this->makeAdmin();
        $field = \App\Models\ApplicationFormField::where('key', 'department')->firstOrFail();
        $system = \App\Models\ApplicationFormField::where('key', 'name')->firstOrFail();

        $this->actingAs($admin)->get('/admin/application-form')->assertOk();
        $this->actingAs($admin)->get('/admin/application-form/create')->assertOk();
        $this->actingAs($admin)->get("/admin/application-form/{$field->id}/edit")->assertOk();
        // The system variant renders a different (locked) form.
        $this->actingAs($admin)->get("/admin/application-form/{$system->id}/edit")->assertOk();
        $this->actingAs($admin)->get('/admin/application-form-sections')->assertOk();
    }
}
