<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Jobs\PostDiscordWebhook;
use App\Jobs\SendDiscordDirectMessage;
use App\Models\CalendarEvent;
use App\Models\Task;
use App\Models\TeamMember;
use App\Models\User;
use App\Support\DiscordDelivery;
use App\Support\DiscordPayloads;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The contract for Discord delivery, now that there are two channels:
 *
 *   - the channel webhook fires for everything, as it always did;
 *   - a DM goes to each recipient who has a snowflake, *as well as*,
 *     never instead of;
 *   - the DM says the same thing without the @-mention, which only
 *     exists so a reader of a shared channel knows who it is for;
 *   - calendar events reach the whole roster, changes and cancellations
 *     included, minus whoever made the change.
 *
 * A member with no snowflake losing nothing is the important half: it is
 * what makes collecting IDs an upgrade rather than a migration.
 */
class DiscordDualDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private const SNOWFLAKE = '123456789012345678';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
    }

    public function test_recipient_with_a_snowflake_gets_both_the_channel_post_and_a_dm(): void
    {
        Bus::fake();

        $user = $this->memberWithDiscord('handle.one', self::SNOWFLAKE);
        $task = $this->makeTask($user);

        DiscordDelivery::toRecipient($user, DiscordPayloads::taskAssignedPing($task, $user));

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        Bus::assertDispatchedTimes(SendDiscordDirectMessage::class, 1);
        Bus::assertDispatched(
            SendDiscordDirectMessage::class,
            fn (SendDiscordDirectMessage $job) => $job->snowflake === self::SNOWFLAKE,
        );
    }

    public function test_recipient_without_a_snowflake_still_gets_the_channel_post(): void
    {
        Bus::fake();

        $user = $this->memberWithDiscord('handle.two', null);
        $task = $this->makeTask($user);

        DiscordDelivery::toRecipient($user, DiscordPayloads::taskAssignedPing($task, $user));

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        Bus::assertNotDispatched(SendDiscordDirectMessage::class);
    }

    public function test_recipient_with_no_discord_identity_at_all_gets_nothing(): void
    {
        Bus::fake();

        $user = $this->makeMember();
        $task = $this->makeTask($user);

        DiscordDelivery::toRecipient($user, DiscordPayloads::taskAssignedPing($task, $user));

        Bus::assertNotDispatched(PostDiscordWebhook::class);
        Bus::assertNotDispatched(SendDiscordDirectMessage::class);
    }

    public function test_the_dm_drops_the_mention_the_channel_post_needs(): void
    {
        Bus::fake();

        $user = $this->memberWithDiscord('handle.three', self::SNOWFLAKE);
        $task = $this->makeTask($user);

        DiscordDelivery::toRecipient($user, DiscordPayloads::taskAssignedPing($task, $user));

        Bus::assertDispatched(
            PostDiscordWebhook::class,
            fn (PostDiscordWebhook $job) => str_contains($job->content, '<@' . self::SNOWFLAKE . '>'),
        );
        Bus::assertDispatched(
            SendDiscordDirectMessage::class,
            fn (SendDiscordDirectMessage $job) => ! str_contains($job->content, '<@')
                && ! str_contains($job->content, '@handle.three')
                // Same event, same embed — only the address line differs.
                && $job->embed['title'] === $task->title,
        );
    }

    public function test_every_task_payload_carries_a_mention_free_dm_line(): void
    {
        $user = $this->memberWithDiscord('handle.four', self::SNOWFLAKE);
        $task = $this->makeTask($user);
        $comment = $task->comments()->create(['user_id' => $user->id, 'body' => 'a comment']);

        $payloads = [
            DiscordPayloads::taskAssignedPing($task, $user),
            DiscordPayloads::taskStatusChangedPing($task, $user, Task::STATUS_TODO, Task::STATUS_IN_PROGRESS),
            DiscordPayloads::taskCommentedPing($task, $user, $comment),
        ];

        foreach ($payloads as $payload) {
            $this->assertArrayHasKey('dm_content', $payload);
            $this->assertStringContainsString('<@', $payload['content']);
            $this->assertStringNotContainsString('<@', $payload['dm_content']);
        }
    }

    public function test_audience_is_rostered_members_with_a_snowflake_minus_the_actor(): void
    {
        $actor = $this->memberWithDiscord('actor', '111111111111111111');
        $included = $this->memberWithDiscord('included', '222222222222222222');
        $noSnowflake = $this->memberWithDiscord('handle.only', null);
        $departed = $this->memberWithDiscord('departed', '333333333333333333', left: true);
        $notOnRoster = $this->makeMember();

        $audience = DiscordDelivery::audience($actor->id)->pluck('id');

        $this->assertTrue($audience->contains($included->id));
        $this->assertFalse($audience->contains($actor->id));
        $this->assertFalse($audience->contains($noSnowflake->id));
        $this->assertFalse($audience->contains($departed->id));
        $this->assertFalse($audience->contains($notOnRoster->id));
    }

    public function test_creating_a_calendar_event_dms_the_whole_roster(): void
    {
        Bus::fake();

        $admin = $this->adminWithDiscord('boss', '999999999999999999');
        $a = $this->memberWithDiscord('cal.one', '111111111111111111');
        $b = $this->memberWithDiscord('cal.two', '222222222222222222');
        $this->memberWithDiscord('cal.three', null);

        $this->actingAs($admin);

        Livewire::test(Calendar::class)
            ->set('eventTitle', 'Launch rehearsal')
            ->set('eventStart', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('eventEnd', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
            ->call('saveEvent');

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        // Two snowflakes on the roster besides the admin who created it.
        Bus::assertDispatchedTimes(SendDiscordDirectMessage::class, 2);

        foreach ([$a, $b] as $user) {
            $snowflake = $user->teamMember->discord_id;
            Bus::assertDispatched(
                SendDiscordDirectMessage::class,
                fn (SendDiscordDirectMessage $job) => $job->snowflake === $snowflake
                    && $job->embed['title'] === 'Launch rehearsal',
            );
        }

        Bus::assertNotDispatched(
            SendDiscordDirectMessage::class,
            fn (SendDiscordDirectMessage $job) => $job->snowflake === '999999999999999999',
        );
    }

    public function test_changing_a_calendar_event_announces_what_changed(): void
    {
        $admin = $this->adminWithDiscord('boss2', '999999999999999999');
        $this->memberWithDiscord('cal.four', '444444444444444444');
        $event = $this->makeEvent($admin);

        $this->actingAs($admin);
        Bus::fake();

        Livewire::test(Calendar::class)
            ->call('openEditModal', $event->id)
            ->set('eventLocation', 'Hangar 2')
            ->call('saveEvent');

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        Bus::assertDispatchedTimes(SendDiscordDirectMessage::class, 1);

        Bus::assertDispatched(PostDiscordWebhook::class, function (PostDiscordWebhook $job) {
            $changed = collect($job->embed['fields'])->firstWhere('name', 'Changed');

            return str_contains($job->reference, 'calendar-event:update:')
                && $changed !== null
                && $changed['value'] === 'Location';
        });
    }

    public function test_a_cosmetic_change_announces_nothing(): void
    {
        $admin = $this->adminWithDiscord('boss3', '999999999999999999');
        $this->memberWithDiscord('cal.five', '555555555555555555');
        $event = $this->makeEvent($admin);

        $this->actingAs($admin);
        Bus::fake();

        Livewire::test(Calendar::class)
            ->call('openEditModal', $event->id)
            ->set('eventColor', '#ff0000')
            ->call('saveEvent');

        $this->assertSame('#ff0000', $event->fresh()->color);
        Bus::assertNotDispatched(PostDiscordWebhook::class);
        Bus::assertNotDispatched(SendDiscordDirectMessage::class);
    }

    public function test_resaving_an_unchanged_event_announces_nothing(): void
    {
        $admin = $this->adminWithDiscord('boss4', '999999999999999999');
        $this->memberWithDiscord('cal.six', '666666666666666666');
        $event = $this->makeEvent($admin);

        $this->actingAs($admin);
        Bus::fake();

        Livewire::test(Calendar::class)
            ->call('openEditModal', $event->id)
            ->call('saveEvent');

        Bus::assertNotDispatched(PostDiscordWebhook::class);
        Bus::assertNotDispatched(SendDiscordDirectMessage::class);
    }

    public function test_deleting_a_calendar_event_announces_it_while_still_describing_it(): void
    {
        $admin = $this->adminWithDiscord('boss5', '999999999999999999');
        $this->memberWithDiscord('cal.seven', '777777777777777777');
        $event = $this->makeEvent($admin);

        $this->actingAs($admin);
        Bus::fake();

        Livewire::test(Calendar::class)
            ->call('openEditModal', $event->id)
            ->call('deleteEvent');

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);

        Bus::assertDispatchedTimes(PostDiscordWebhook::class, 1);
        Bus::assertDispatchedTimes(SendDiscordDirectMessage::class, 1);

        // The embed has to survive the row it describes — it is built
        // before the delete for exactly this reason.
        Bus::assertDispatched(
            SendDiscordDirectMessage::class,
            fn (SendDiscordDirectMessage $job) => $job->embed['title'] === $event->title
                && $job->embed['fields'] !== []
                && str_contains($job->reference, 'calendar-event:delete:'),
        );
    }

    private function makeEvent(User $owner): CalendarEvent
    {
        return CalendarEvent::create([
            'user_id' => $owner->id,
            'title' => 'Static fire',
            'description' => 'Hold-down test',
            'start_at' => now()->addWeek()->startOfHour(),
            'end_at' => now()->addWeek()->startOfHour()->addHours(2),
            'all_day' => false,
            'color' => '#0ea5e9',
        ]);
    }

    private function makeTask(User $supervisor): Task
    {
        return Task::create([
            'title' => 'Discord probe ' . uniqid(),
            'description' => 'A description that easily exceeds ten characters.',
            'status' => Task::STATUS_TODO,
            'supervisor_id' => $supervisor->id,
            'created_by' => $supervisor->id,
            'due_date' => now()->addWeek(),
            'position' => 0,
        ]);
    }

    private function memberWithDiscord(string $username, ?string $snowflake, bool $left = false): User
    {
        return $this->attachDiscord($this->makeMember(), $username, $snowflake, $left);
    }

    private function adminWithDiscord(string $username, ?string $snowflake): User
    {
        return $this->attachDiscord($this->makeAdmin(), $username, $snowflake);
    }

    private function attachDiscord(User $user, string $username, ?string $snowflake, bool $left = false): User
    {
        TeamMember::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'discord_username' => $username,
            'discord_id' => $snowflake,
            'left_at' => $left ? now()->subMonth() : null,
        ]);

        return $user->fresh();
    }
}
