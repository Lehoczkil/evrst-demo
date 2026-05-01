<?php

namespace Tests\Unit;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\DiscordPayloads;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordPayloadsMentionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_wants_discord_ping_false_without_team_member(): void
    {
        $user = $this->makeMember();

        $this->assertFalse(DiscordPayloads::wantsDiscordPing($user));
    }

    public function test_wants_discord_ping_true_with_only_nick(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_nick' => 'NickOnly',
        ]);

        $this->assertTrue(DiscordPayloads::wantsDiscordPing($user->fresh()));
    }

    public function test_wants_discord_ping_true_with_only_username(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_username' => 'handle.only',
        ]);

        $this->assertTrue(DiscordPayloads::wantsDiscordPing($user->fresh()));
    }

    public function test_wants_discord_ping_true_with_snowflake(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_id' => '123456789012345678',
        ]);

        $this->assertTrue(DiscordPayloads::wantsDiscordPing($user->fresh()));
    }

    public function test_mention_uses_snowflake_when_discord_id_is_17_to_20_digits(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_id' => '123456789012345678',
            'discord_nick' => 'Tester',
        ]);

        $task = $this->makeTask($user);
        $payload = DiscordPayloads::taskAssignedPing($task, $user->fresh());

        $this->assertStringContainsString('<@123456789012345678>', $payload['content']);
        $this->assertStringContainsString('@Tester', $payload['content']);
    }

    public function test_mention_falls_back_to_nick_when_id_is_not_snowflake(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            // 16 digits — too short for snowflake regex.
            'discord_id' => '1234567890123456',
            'discord_nick' => 'Plainnick',
        ]);

        $task = $this->makeTask($user);
        $payload = DiscordPayloads::taskAssignedPing($task, $user->fresh());

        $this->assertStringNotContainsString('<@', $payload['content']);
        $this->assertStringContainsString('@Plainnick', $payload['content']);
    }

    public function test_mention_falls_back_to_username_when_no_nick(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_username' => 'usernameonly',
        ]);

        $task = $this->makeTask($user);
        $payload = DiscordPayloads::taskStatusChangedPing($task, $user->fresh(), 'TODO', 'IN_PROGRESS');

        $this->assertStringContainsString('@usernameonly', $payload['content']);
        $this->assertStringNotContainsString('<@', $payload['content']);
    }

    public function test_mention_falls_back_to_user_name_when_team_member_has_no_discord_data(): void
    {
        $user = $this->makeMember(['name' => 'Plain User']);
        // No TeamMember at all — mention should print `$user->name`.

        $task = $this->makeTask($user);
        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'body' => 'hi',
        ]);
        $payload = DiscordPayloads::taskCommentedPing($task, $user->fresh(), $comment);

        $this->assertStringContainsString('Plain User', $payload['content']);
        $this->assertStringNotContainsString('<@', $payload['content']);
    }

    public function test_mention_rejects_21_digit_id_as_non_snowflake(): void
    {
        $user = $this->makeMember();
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_id' => '123456789012345678901', // 21 digits.
            'discord_nick' => 'Edge',
        ]);

        $task = $this->makeTask($user);
        $payload = DiscordPayloads::taskAssignedPing($task, $user->fresh());

        $this->assertStringNotContainsString('<@', $payload['content']);
        $this->assertStringContainsString('@Edge', $payload['content']);
    }

    private function makeTask(User $supervisor): Task
    {
        return Task::create([
            'title' => 'Mention probe',
            'description' => 'A description that easily exceeds ten characters.',
            'status' => 'TODO',
            'supervisor_id' => $supervisor->id,
            'created_by' => $supervisor->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);
    }
}
