<?php

namespace Tests\Feature;

use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\KanbanBoard;
use App\Models\Task;
use App\Models\TaskProof;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The documented state machine — assignee moves a task to TESTING with
 * proof attached, only the supervisor marks it DONE — was written for the
 * Member role, but every gate asked for TASKS_EDIT, which Member does not
 * hold. The edit page 403'd, so the proof and comment relation managers
 * built for that flow never rendered.
 *
 * `tasks.progress` is the key that opens it, always paired with an
 * assignee / supervisor check on the record.
 */
class MemberTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $assignee;

    private User $supervisor;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Bus::fake();

        $this->assignee = $this->makeMember(['name' => 'Assignee']);
        $this->supervisor = $this->makeMember(['name' => 'Supervisor']);

        $this->task = Task::create([
            'title' => 'Fit the nose cone',
            'description' => 'Long enough to satisfy the minimum length rule.',
            'status' => Task::STATUS_IN_PROGRESS,
            'priority' => Task::PRIORITY_NORMAL,
            'supervisor_id' => $this->supervisor->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);
        $this->task->assignees()->sync([$this->assignee->id]);
    }

    private function proof(): TaskProof
    {
        return TaskProof::create([
            'task_id' => $this->task->id,
            'user_id' => $this->assignee->id,
            'kind' => TaskProof::KIND_NOTE,
            'title' => 'Fitted and measured',
            'body' => 'Photos in the shared drive.',
        ]);
    }

    public function test_an_assignee_reaches_the_task_edit_page(): void
    {
        $this->actingAs($this->assignee)
            ->get("/admin/tasks/{$this->task->id}/edit")
            ->assertOk();
    }

    public function test_an_unrelated_member_does_not(): void
    {
        $stranger = $this->makeMember(['name' => 'Stranger']);

        $response = $this->actingAs($stranger)->get("/admin/tasks/{$this->task->id}/edit");

        $this->assertContains($response->status(), [302, 403, 404]);
    }

    public function test_an_assignee_can_move_to_testing_once_proof_exists(): void
    {
        $this->assertFalse(
            $this->task->canTransitionTo($this->assignee, Task::STATUS_TESTING),
            'TESTING without proof must stay closed',
        );

        $this->proof();

        $this->assertTrue($this->task->fresh()->canTransitionTo($this->assignee, Task::STATUS_TESTING));
    }

    public function test_only_the_supervisor_marks_it_done(): void
    {
        $this->proof();
        $task = $this->task->fresh();

        $this->assertFalse($task->canTransitionTo($this->assignee, Task::STATUS_DONE));
        $this->assertTrue($task->canTransitionTo($this->supervisor, Task::STATUS_DONE));
    }

    public function test_an_assignee_cannot_rewrite_the_task_definition(): void
    {
        $this->proof();

        Livewire::actingAs($this->assignee)
            ->test(EditTask::class, ['record' => $this->task->id])
            ->assertFormFieldDisabled('title')
            ->assertFormFieldDisabled('assignees')
            ->assertFormFieldDisabled('supervisor_id')
            ->assertFormFieldDisabled('due_date')
            ->fillForm(['status' => Task::STATUS_TESTING])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $this->task->fresh();
        $this->assertSame(Task::STATUS_TESTING, $fresh->status);
        $this->assertSame('Fit the nose cone', $fresh->title, 'a locked field must survive the save');
    }

    public function test_an_admin_keeps_full_edit_rights(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(EditTask::class, ['record' => $this->task->id])
            ->assertFormFieldEnabled('title')
            ->assertFormFieldEnabled('assignees');
    }

    public function test_the_kanban_lets_an_assignee_drag_their_own_card_only(): void
    {
        $this->proof();

        $other = Task::create([
            'title' => 'Someone else\'s task',
            'description' => 'Long enough to satisfy the minimum length rule.',
            'status' => Task::STATUS_IN_PROGRESS,
            'priority' => Task::PRIORITY_NORMAL,
            'supervisor_id' => $this->supervisor->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);

        Livewire::actingAs($this->assignee)
            ->test(KanbanBoard::class)
            ->call('reorder', [
                Task::STATUS_TESTING => [$this->task->id, $other->id],
            ]);

        $this->assertSame(Task::STATUS_TESTING, $this->task->fresh()->status);
        $this->assertSame(
            Task::STATUS_IN_PROGRESS,
            $other->fresh()->status,
            'a card the member is not on must snap back',
        );
    }
}
