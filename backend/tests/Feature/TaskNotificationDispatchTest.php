<?php

namespace Tests\Feature;

use App\Filament\Resources\Tasks\Pages\CreateTask;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\KanbanBoard;
use App\Filament\Resources\Tasks\RelationManagers\CommentsRelationManager;
use App\Jobs\PostDiscordWebhook;
use App\Models\Task;
use App\Models\TaskProof;
use App\Models\TeamMember;
use App\Models\User;
use App\Notifications\TaskAssigned;
use App\Notifications\TaskCommented;
use App\Notifications\TaskStatusChanged;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class TaskNotificationDispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_create_task_notifies_new_assignees_excluding_actor(): void
    {
        Notification::fake();
        Bus::fake();

        $admin = $this->makeAdmin();
        $assigneeWithDiscord = $this->memberWithDiscord('handle.one');
        $assigneeNoDiscord = $this->makeMember();

        $this->actingAs($admin);

        Livewire::test(CreateTask::class)
            ->fillForm([
                'title' => 'Notify probe',
                'description' => 'A description that easily exceeds ten characters.',
                'status' => Task::STATUS_TODO,
                'supervisor_id' => $admin->id,
                'due_date' => now()->addWeek()->toDateString(),
                'priority' => Task::PRIORITY_NORMAL,
                'assignees' => [$assigneeWithDiscord->id, $assigneeNoDiscord->id, $admin->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($assigneeWithDiscord, TaskAssigned::class);
        Notification::assertSentTo($assigneeNoDiscord, TaskAssigned::class);
        Notification::assertNotSentTo($admin, TaskAssigned::class);

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        Bus::assertDispatched(
            PostDiscordWebhook::class,
            fn (PostDiscordWebhook $job) => str_contains($job->reference, ':' . $assigneeWithDiscord->id),
        );
    }

    public function test_edit_task_notifies_only_newly_added_assignees(): void
    {
        Notification::fake();
        Bus::fake();

        $admin = $this->makeAdmin();
        $existing = $this->makeMember();
        $newcomer = $this->memberWithDiscord('newhandle');

        $task = $this->makeTask($admin);
        $task->assignees()->attach($existing->id);

        $this->actingAs($admin);

        Livewire::test(EditTask::class, ['record' => $task->id])
            ->fillForm([
                'assignees' => [$existing->id, $newcomer->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($newcomer, TaskAssigned::class);
        Notification::assertNotSentTo($existing, TaskAssigned::class);
        Notification::assertNotSentTo($admin, TaskAssigned::class);

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
    }

    public function test_edit_task_status_change_notifies_watchers_excluding_actor(): void
    {
        Notification::fake();
        Bus::fake();

        $admin = $this->makeAdmin();
        // Manager role holds tasks.edit; member role does not, and the
        // EditTask page would 403 the assignee otherwise.
        $assigneeUser = $this->makeManager();
        $assignee = $this->attachDiscord($assigneeUser, 'assigneehandle');

        $task = $this->makeTask($admin);
        $task->assignees()->attach($assignee->id);

        $this->actingAs($assignee);

        Livewire::test(EditTask::class, ['record' => $task->id])
            ->fillForm([
                'status' => Task::STATUS_IN_PROGRESS,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Notification::assertSentTo($admin, TaskStatusChanged::class);
        Notification::assertNotSentTo($assignee, TaskStatusChanged::class);

        // Admin user has no TeamMember/discord data → no Discord ping.
        Bus::assertNotDispatched(PostDiscordWebhook::class);
    }

    public function test_kanban_status_change_notifies_watchers_and_pings_discord_only_for_those_with_handles(): void
    {
        Notification::fake();
        Bus::fake();

        $admin = $this->makeAdmin();
        $supervisorWithDiscord = $this->memberWithDiscord('superhandle');
        $assigneeNoDiscord = $this->makeMember();

        $task = $this->makeTask($supervisorWithDiscord);
        $task->assignees()->attach($assigneeNoDiscord->id);

        $this->actingAs($admin);

        Livewire::test(KanbanBoard::class)
            ->call('reorder', [
                Task::STATUS_TODO => [],
                Task::STATUS_IN_PROGRESS => [$task->id],
                Task::STATUS_TESTING => [],
                Task::STATUS_DONE => [],
            ]);

        $this->assertSame(Task::STATUS_IN_PROGRESS, $task->fresh()->status);

        Notification::assertSentTo($supervisorWithDiscord, TaskStatusChanged::class);
        Notification::assertSentTo($assigneeNoDiscord, TaskStatusChanged::class);
        Notification::assertNotSentTo($admin, TaskStatusChanged::class);

        // One discord dispatch — only the supervisor has discord data.
        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
    }

    public function test_comment_create_notifies_watchers_excluding_actor(): void
    {
        Notification::fake();
        Bus::fake();

        $admin = $this->makeAdmin();
        $supervisor = $this->memberWithDiscord('supervisorhandle');
        $assignee = $this->makeMember();

        $task = $this->makeTask($supervisor);
        $task->assignees()->attach($assignee->id);

        $this->actingAs($assignee);

        Livewire::test(CommentsRelationManager::class, [
            'ownerRecord' => $task,
            'pageClass' => EditTask::class,
        ])
            ->callTableAction('create', data: ['body' => 'first comment'])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('task_comments', ['task_id' => $task->id, 'body' => 'first comment']);

        Notification::assertSentTo($supervisor, TaskCommented::class);
        Notification::assertNotSentTo($assignee, TaskCommented::class);

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
    }

    private function makeTask(User $supervisor): Task
    {
        $task = Task::create([
            'title' => 'Notify ' . uniqid(),
            'description' => 'A description that easily exceeds ten characters.',
            'status' => Task::STATUS_TODO,
            'supervisor_id' => $supervisor->id,
            'created_by' => $supervisor->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);
        return $task;
    }

    private function memberWithDiscord(string $username): User
    {
        return $this->attachDiscord($this->makeMember(), $username);
    }

    private function attachDiscord(User $user, string $username): User
    {
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_username' => $username,
        ]);
        return $user->fresh();
    }
}
