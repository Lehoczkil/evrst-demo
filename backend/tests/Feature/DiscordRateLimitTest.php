<?php

namespace Tests\Feature;

use App\Jobs\PostDiscordWebhook;
use App\Jobs\SendDiscordDirectMessage;
use App\Services\DiscordBot;
use Illuminate\Contracts\Queue\Job as QueueJobContract;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Discord answers a rate limit with the exact wait to use. Both jobs
 * release themselves for that interval instead of throwing, because the
 * fixed backoff would burn one of only three attempts on a guess — and a
 * roster-wide calendar announcement is precisely the moment the limiter
 * fires, when nothing has gone wrong at all.
 */
class DiscordRateLimitTest extends TestCase
{
    public function test_webhook_job_releases_for_the_interval_discord_asks_for(): void
    {
        config(['services.discord.webhook' => 'https://discord.com/api/webhooks/1/abc']);
        Http::fake([
            '*' => Http::response(['retry_after' => 4.2], 429),
        ]);

        $job = new PostDiscordWebhook('hi', ['title' => 'x'], 'ref');
        $job->setJob($this->expectRelease(5));

        $job->handle();
    }

    public function test_webhook_job_falls_back_to_the_retry_after_header(): void
    {
        config(['services.discord.webhook' => 'https://discord.com/api/webhooks/1/abc']);
        Http::fake([
            '*' => Http::response('rate limited', 429, ['Retry-After' => '7']),
        ]);

        $job = new PostDiscordWebhook('hi', ['title' => 'x'], 'ref');
        $job->setJob($this->expectRelease(7));

        $job->handle();
    }

    public function test_dm_job_releases_when_opening_the_channel_is_rate_limited(): void
    {
        config([
            'services.discord.bot_token' => 'fake-bot-token',
            'services.discord.api_base' => 'https://discord.com/api/v10',
        ]);
        Http::fake([
            'discord.com/api/v10/users/@me/channels' => Http::response(['retry_after' => 2], 429),
        ]);

        $job = new SendDiscordDirectMessage('123456789012345678', 'hello');
        $job->setJob($this->expectRelease(2));

        $job->handle(app(DiscordBot::class));

        // Released, not sent: the message must not go out twice.
        Http::assertSentCount(1);
    }

    public function test_dm_job_gives_up_quietly_when_the_recipient_has_dms_closed(): void
    {
        config([
            'services.discord.bot_token' => 'fake-bot-token',
            'services.discord.api_base' => 'https://discord.com/api/v10',
        ]);
        Http::fake([
            'discord.com/api/v10/users/@me/channels' => Http::response(['message' => 'Cannot send messages to this user'], 403),
        ]);

        $job = new SendDiscordDirectMessage('123456789012345678', 'hello');
        $job->setJob($this->neverReleases());

        // No exception: retrying cannot change the recipient's privacy
        // setting, and the channel post already carried the message.
        $job->handle(app(DiscordBot::class));

        Http::assertSentCount(1);
    }

    private function expectRelease(int $seconds): QueueJobContract
    {
        $mock = Mockery::mock(QueueJobContract::class);
        $mock->shouldReceive('release')->once()->with($seconds);
        $mock->shouldIgnoreMissing();

        return $mock;
    }

    private function neverReleases(): QueueJobContract
    {
        $mock = Mockery::mock(QueueJobContract::class);
        $mock->shouldReceive('release')->never();
        $mock->shouldIgnoreMissing();

        return $mock;
    }
}
