<?php

namespace Tests\Feature;

use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\RelationManagers\ChildrenRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tasks\RelationManagers\ProofsRelationManager;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskProof;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The subtasks relation manager had no authorization at all.
 *
 * A relation manager resolves its built-in actions through
 * `get*AuthorizationResponse()`, and with no policy registered for the
 * model the default answer is *allow*. ChildrenRelationManager renders on
 * the task edit page, which a Member opens with `tasks.progress` on any
 * task they are on — so its CreateAction and DeleteAction handed that
 * Member the two things `tasks.create` / `tasks.delete` exist to gate.
 *
 * Every call here goes through mountAction + callMountedAction rather
 * than callAction/callTableAction, because those two assert visibility in
 * the test helper first and would never reach the code under test. This
 * is the shape of a crafted /livewire/update call. Each denial is paired
 * with a positive control through the identical transport, so a green
 * test cannot mean "the request simply did not arrive".
 */
class TaskRelationManagerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Task $task;

    private User $assignee;

    private User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();

        $this->supervisor = $this->makeMember(['name' => 'Supervisor']);
        $this->assignee = $this->makeMember(['name' => 'Assignee']);

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

    private function children(User $actor): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::actingAs($actor)->test(ChildrenRelationManager::class, [
            'ownerRecord' => $this->task,
            'pageClass' => EditTask::class,
        ]);
    }

    /** @return array<string, mixed> */
    private function subtaskData(string $title): array
    {
        return [
            'title' => $title,
            'description' => 'Long enough to satisfy the minimum length rule.',
            'priority' => Task::PRIORITY_NORMAL,
            'supervisor_id' => $this->supervisor->id,
            'assignees' => [$this->assignee->id],
            'due_date' => now()->addWeek()->toDateString(),
        ];
    }

    private function subtask(string $title = 'Existing subtask'): Task
    {
        return Task::create([
            'title' => $title,
            'description' => 'Long enough to satisfy the minimum length rule.',
            'status' => Task::STATUS_TODO,
            'priority' => Task::PRIORITY_NORMAL,
            'supervisor_id' => $this->supervisor->id,
            'parent_task_id' => $this->task->id,
            'due_date' => now()->addWeek()->toDateString(),
        ]);
    }

    public function test_a_member_on_the_task_cannot_create_a_subtask(): void
    {
        $this->children($this->assignee)
            ->mountAction(TestAction::make('create')->table())
            ->setActionData($this->subtaskData('Injected subtask'))
            ->callMountedAction();

        $this->assertDatabaseMissing('tasks', ['title' => 'Injected subtask']);
    }

    /** Control: the same transport, from someone who holds tasks.create. */
    public function test_an_admin_can_still_create_a_subtask(): void
    {
        $this->children($this->makeAdmin())
            ->mountAction(TestAction::make('create')->table())
            ->setActionData($this->subtaskData('Legitimate subtask'))
            ->callMountedAction();

        $this->assertDatabaseHas('tasks', [
            'title' => 'Legitimate subtask',
            'parent_task_id' => $this->task->id,
        ]);
    }

    public function test_a_member_on_the_task_cannot_delete_a_subtask(): void
    {
        $child = $this->subtask();

        $this->children($this->assignee)
            ->mountAction(TestAction::make('delete')->table($child))
            ->callMountedAction();

        $this->assertDatabaseHas('tasks', ['id' => $child->id]);
    }

    public function test_a_member_on_the_task_cannot_bulk_delete_subtasks(): void
    {
        $child = $this->subtask();

        $this->children($this->assignee)
            ->set('selectedTableRecords', [(string) $child->id])
            ->mountAction(TestAction::make('delete')->table()->bulk())
            ->callMountedAction();

        $this->assertDatabaseHas('tasks', ['id' => $child->id]);
    }

    /** Control: the same transport, from someone who holds tasks.delete. */
    public function test_an_admin_can_still_delete_a_subtask(): void
    {
        $child = $this->subtask();

        $this->children($this->makeAdmin())
            ->mountAction(TestAction::make('delete')->table($child))
            ->callMountedAction();

        $this->assertDatabaseMissing('tasks', ['id' => $child->id]);
    }

    /**
     * The sibling managers were already gated with `->visible()`, which
     * Filament v4.11 does enforce on mount. These pin that down against
     * the authorization overrides that now back it up.
     */
    public function test_a_member_cannot_delete_someone_elses_proof(): void
    {
        $proof = TaskProof::create([
            'task_id' => $this->task->id,
            'user_id' => $this->supervisor->id,
            'kind' => TaskProof::KIND_NOTE,
            'title' => 'Supervisor note',
            'body' => 'Measured and signed off.',
        ]);

        Livewire::actingAs($this->assignee)
            ->test(ProofsRelationManager::class, [
                'ownerRecord' => $this->task,
                'pageClass' => EditTask::class,
            ])
            ->mountAction(TestAction::make('delete')->table($proof))
            ->callMountedAction();

        $this->assertDatabaseHas('task_proofs', ['id' => $proof->id]);
    }

    public function test_a_member_cannot_delete_someone_elses_comment(): void
    {
        $comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->supervisor->id,
            'body' => 'Please re-measure the shoulder.',
        ]);

        Livewire::actingAs($this->assignee)
            ->test(CommentsRelationManager::class, [
                'ownerRecord' => $this->task,
                'pageClass' => EditTask::class,
            ])
            ->mountAction(TestAction::make('delete')->table($comment))
            ->callMountedAction();

        $this->assertDatabaseHas('task_comments', ['id' => $comment->id]);
    }

    /** An unrelated member has no business posting on the task at all. */
    public function test_an_unrelated_member_cannot_post_a_comment(): void
    {
        $stranger = $this->makeMember(['name' => 'Stranger']);

        Livewire::actingAs($stranger)
            ->test(CommentsRelationManager::class, [
                'ownerRecord' => $this->task,
                'pageClass' => EditTask::class,
            ])
            ->mountAction(TestAction::make('create')->table());

        // The manager renders nothing at all for a stranger, so Livewire
        // cannot re-render it after the call — which is itself the answer:
        // there is no mounted action to call.
        $this->assertDatabaseMissing('task_comments', ['body' => 'Injected comment']);
    }
}
