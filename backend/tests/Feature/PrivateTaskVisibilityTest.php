<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Jobs\PostDiscordWebhook;
use App\Jobs\SendDiscordDirectMessage;
use App\Filament\Resources\Tasks\Pages\EditTask;
use App\Filament\Resources\Tasks\Pages\KanbanBoard;
use App\Filament\Resources\Tasks\Pages\ListTasks;
use App\Filament\Resources\Tasks\TaskResource;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use App\Support\DiscordDelivery;
use App\Support\DiscordPayloads;
use Tests\TestCase;

/**
 * A private task is off the board for everyone except the admins and the
 * people actually on it.
 *
 * The rule lives in one place — `Task::scopeVisibleTo()` for lists and
 * `isVisibleTo()` for a record already in hand — but it has to hold at
 * every surface a task can appear on, which is what most of this file is
 * about. A leak here is not a cosmetic bug: it is the whole feature.
 */
class PrivateTaskVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Bus::fake();
    }

    public function test_an_admin_sees_a_private_task(): void
    {
        $admin = $this->makeAdmin();
        $task = $this->privateTask($this->makeAdmin());

        $this->assertTrue($task->isVisibleTo($admin));
        $this->assertTrue(Task::visibleTo($admin)->whereKey($task->id)->exists());
    }

    public function test_a_manager_not_on_the_task_does_not(): void
    {
        $manager = $this->makeManager();
        $task = $this->privateTask($this->makeAdmin());

        $this->assertFalse($task->isVisibleTo($manager));
        $this->assertFalse(Task::visibleTo($manager)->whereKey($task->id)->exists());
    }

    public function test_an_assignee_sees_the_private_task_they_are_on(): void
    {
        $member = $this->makeMember();
        $task = $this->privateTask($this->makeAdmin());
        $task->assignees()->attach($member->id);

        $this->assertTrue($task->fresh()->isVisibleTo($member));
        $this->assertTrue(Task::visibleTo($member)->whereKey($task->id)->exists());
    }

    public function test_the_supervisor_sees_the_private_task_they_supervise(): void
    {
        $supervisor = $this->makeManager();
        $task = $this->privateTask($supervisor);

        $this->assertTrue($task->isVisibleTo($supervisor));
        $this->assertTrue(Task::visibleTo($supervisor)->whereKey($task->id)->exists());
    }

    public function test_a_public_task_stays_visible_to_everyone(): void
    {
        $task = $this->makeTask($this->makeAdmin());

        foreach ([$this->makeAdmin(), $this->makeManager(), $this->makeMember()] as $user) {
            $this->assertTrue($task->isVisibleTo($user));
        }
    }

    public function test_nobody_is_not_everybody(): void
    {
        $task = $this->privateTask($this->makeAdmin());

        $this->assertFalse($task->isVisibleTo(null));
        $this->assertFalse(Task::visibleTo(null)->exists());
    }

    public function test_the_task_list_hides_it(): void
    {
        $admin = $this->makeAdmin();
        $private = $this->privateTask($admin);
        $public = $this->makeTask($admin);

        $this->actingAs($this->makeManager());

        Livewire::test(ListTasks::class)
            ->assertCanSeeTableRecords([$public])
            ->assertCanNotSeeTableRecords([$private]);
    }

    public function test_the_kanban_hides_it(): void
    {
        $admin = $this->makeAdmin();
        $private = $this->privateTask($admin);
        $public = $this->makeTask($admin);

        $this->actingAs($this->makeManager());

        $columns = Livewire::test(KanbanBoard::class)->instance()->getColumns();
        $ids = collect($columns)->pluck('tasks')->flatten(1)->pluck('id');

        $this->assertTrue($ids->contains($public->id));
        $this->assertFalse($ids->contains($private->id));
    }

    public function test_the_kanban_will_not_reorder_a_task_you_cannot_see(): void
    {
        $private = $this->privateTask($this->makeAdmin());
        $this->assertSame(Task::STATUS_TODO, $private->status);

        $this->actingAs($this->makeManager());

        Livewire::test(KanbanBoard::class)->call('reorder', [
            Task::STATUS_TODO => [],
            Task::STATUS_IN_PROGRESS => [$private->id],
            Task::STATUS_TESTING => [],
            Task::STATUS_DONE => [],
        ]);

        // reorder() is a Livewire endpoint: it takes whatever ids it is
        // handed, so the scope has to be on the lookup, not on the render.
        $this->assertSame(Task::STATUS_TODO, $private->fresh()->status);
    }

    public function test_the_calendar_overlay_hides_it(): void
    {
        $admin = $this->makeAdmin();
        $due = now()->addDays(3);
        $private = $this->privateTask($admin, ['title' => 'Hidden deadline', 'due_date' => $due]);
        $public = $this->makeTask($admin, ['title' => 'Open deadline', 'due_date' => $due]);

        $this->actingAs($this->makeManager());

        $html = Livewire::test(Calendar::class)->html();

        $this->assertStringContainsString('Open deadline', $html);
        $this->assertStringNotContainsString('Hidden deadline', $html);
    }

    public function test_opening_a_private_task_directly_is_a_404_not_a_form(): void
    {
        $private = $this->privateTask($this->makeAdmin());

        // Manager holds tasks.edit, so this is the visibility scope
        // answering, not the permission gate.
        $this->actingAs($this->makeManager());

        $this->get(TaskResource::getUrl('edit', ['record' => $private->id]))
            ->assertNotFound();
    }

    public function test_a_non_admin_cannot_flip_the_flag_through_the_form(): void
    {
        $manager = $this->makeManager();
        $task = $this->makeTask($this->makeAdmin());
        $task->assignees()->attach($manager->id);

        $this->actingAs($manager);

        Livewire::test(EditTask::class, ['record' => $task->id])
            ->assertFormFieldDoesNotExist('is_private')
            ->fillForm(['title' => 'Renamed by a manager'])
            ->call('save')
            ->assertHasNoFormErrors();

        // A field Filament does not render is one it does not dehydrate.
        $this->assertFalse($task->fresh()->is_private);
    }

    public function test_an_admin_can_make_a_task_private_and_public_again(): void
    {
        $admin = $this->makeAdmin();
        $task = $this->makeTask($admin);
        $task->assignees()->attach($admin->id);

        $this->actingAs($admin);

        Livewire::test(EditTask::class, ['record' => $task->id])
            ->fillForm(['is_private' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($task->fresh()->is_private);

        Livewire::test(EditTask::class, ['record' => $task->id])
            ->fillForm(['is_private' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($task->fresh()->is_private);
    }

    public function test_existing_tasks_are_public(): void
    {
        $task = $this->makeTask($this->makeAdmin());

        $this->assertFalse($task->is_private, 'the column must default to public');
    }

    public function test_a_private_task_is_never_announced_in_the_shared_channel(): void
    {
        $assignee = $this->memberWithSnowflake();
        $task = $this->privateTask($this->makeAdmin());
        $task->assignees()->attach($assignee->id);

        DiscordDelivery::toRecipient($assignee, DiscordPayloads::taskAssignedPing($task->fresh(), $assignee));

        // The private copy still goes out — that is the whole point.
        Bus::assertDispatched(SendDiscordDirectMessage::class);
        Bus::assertNotDispatched(PostDiscordWebhook::class);
    }

    public function test_a_public_task_still_reaches_the_channel(): void
    {
        $assignee = $this->memberWithSnowflake();
        $task = $this->makeTask($this->makeAdmin());
        $task->assignees()->attach($assignee->id);

        DiscordDelivery::toRecipient($assignee, DiscordPayloads::taskAssignedPing($task->fresh(), $assignee));

        Bus::assertDispatched(PostDiscordWebhook::class);
        Bus::assertDispatched(SendDiscordDirectMessage::class);
    }

    public function test_the_channel_flag_travels_on_the_payload_not_the_call_site(): void
    {
        $user = $this->memberWithSnowflake();
        $private = $this->privateTask($this->makeAdmin());
        $public = $this->makeTask($this->makeAdmin());

        $this->assertFalse(DiscordPayloads::taskAssignedPing($private, $user)['channel']);
        $this->assertTrue(DiscordPayloads::taskAssignedPing($public, $user)['channel']);

        // toChannel() honours it too, whichever entry point asked.
        DiscordDelivery::toChannel(DiscordPayloads::taskCommentedPing(
            $private,
            $user,
            $private->comments()->create(['user_id' => $user->id, 'body' => 'secret']),
        ));

        Bus::assertNotDispatched(PostDiscordWebhook::class);
    }

    private function memberWithSnowflake(): User
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_username' => 'handle' . $user->id,
            'discord_id' => str_pad((string) $user->id, 18, '1', STR_PAD_LEFT),
        ]);

        return $user->fresh();
    }

    private function privateTask(User $supervisor, array $overrides = []): Task
    {
        return $this->makeTask($supervisor, $overrides + ['is_private' => true]);
    }

    private function makeTask(User $supervisor, array $overrides = []): Task
    {
        return Task::create($overrides + [
            'title' => 'Task ' . uniqid(),
            'description' => 'A description that easily exceeds ten characters.',
            'status' => Task::STATUS_TODO,
            'supervisor_id' => $supervisor->id,
            'created_by' => $supervisor->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);
    }
}
