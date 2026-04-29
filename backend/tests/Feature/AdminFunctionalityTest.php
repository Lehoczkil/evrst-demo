<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\BugReport;
use App\Models\CalendarEvent;
use App\Models\Drawing;
use App\Models\Task;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Model-level behavioural tests — covers the bits of admin
 * functionality that aren't visible in a single page render:
 *  - LogsActivity trait writes activity_logs rows on CRUD.
 *  - Task state machine refuses illegal transitions.
 *  - Bug report status enum is enforced.
 *  - Drawing + CalendarEvent + ActivityLog have their relations.
 */
class AdminFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
    }

    public function test_creating_a_task_writes_an_activity_log(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $task = Task::create([
            'title' => 'Activity logged task',
            'description' => 'A description that easily exceeds ten characters.',
            'status' => 'TODO',
            'supervisor_id' => $admin->id,
            'created_by' => $admin->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'event' => 'created',
            'subject_type' => Task::class,
            'subject_id' => (string) $task->id,
            'user_id' => $admin->id,
        ]);
    }

    public function test_updating_a_task_writes_a_diff(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $task = Task::create([
            'title' => 'Before',
            'description' => 'A description that is plenty long enough.',
            'status' => 'TODO',
            'supervisor_id' => $admin->id,
            'created_by' => $admin->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);

        $task->update(['title' => 'After']);

        $log = ActivityLog::where('subject_id', (string) $task->id)
            ->where('event', 'updated')
            ->latest()->first();

        $this->assertNotNull($log);
        $this->assertSame('Before', $log->changes['title']['from']);
        $this->assertSame('After', $log->changes['title']['to']);
    }

    public function test_task_state_machine_refuses_illegal_transition(): void
    {
        $admin = $this->makeAdmin();
        $assignee = $this->makeMember();
        $this->actingAs($assignee);

        $task = Task::create([
            'title' => 'Gated task',
            'description' => 'A long enough description.',
            'status' => 'TODO',
            'supervisor_id' => $admin->id,
            'created_by' => $admin->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);
        $task->assignees()->attach($assignee->id);

        // Assignees can't jump to DONE — that's supervisor-only AND
        // requires a proof. Save() should throw.
        $this->expectException(\DomainException::class);
        $task->fill(['status' => 'DONE'])->save();
    }

    public function test_bug_report_remembers_status_and_severity(): void
    {
        $admin = $this->makeAdmin();
        $bug = BugReport::create([
            'reporter_id' => $admin->id,
            'title' => 'Test',
            'description' => 'Something broke',
            'status' => BugReport::STATUS_TRIAGING,
            'severity' => BugReport::SEVERITY_HIGH,
        ]);

        $this->assertSame(BugReport::STATUS_TRIAGING, $bug->fresh()->status);
        $this->assertSame(BugReport::SEVERITY_HIGH, $bug->fresh()->severity);
        $this->assertContains($bug->status, BugReport::statuses());
        $this->assertContains($bug->severity, BugReport::severities());
    }

    public function test_calendar_event_creator_relation_resolves(): void
    {
        $admin = $this->makeAdmin();
        $event = CalendarEvent::create([
            'user_id' => $admin->id,
            'title' => 'Standup',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'all_day' => false,
            'color' => '#0ea5e9',
        ]);

        $this->assertSame($admin->id, $event->creator->id);
    }

    public function test_drawings_persist_metadata_for_studio_export(): void
    {
        $admin = $this->makeAdmin();
        $drawing = Drawing::create([
            'user_id' => $admin->id,
            'title' => 'Concept',
            'path' => 'drawings/test.png',
            'disk' => 'public',
            'width' => 1920,
            'height' => 1080,
        ]);

        $this->assertDatabaseHas('drawings', [
            'id' => $drawing->id,
            'user_id' => $admin->id,
            'title' => 'Concept',
        ]);
    }

    public function test_activity_log_attaches_to_subject_label(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        $event = CalendarEvent::create([
            'user_id' => $admin->id,
            'title' => 'Snapshot label',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'all_day' => false,
            'color' => '#0ea5e9',
        ]);

        $log = ActivityLog::where('subject_id', (string) $event->id)
            ->where('event', 'created')
            ->latest()->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Snapshot label', $log->subject_label);
    }
}
