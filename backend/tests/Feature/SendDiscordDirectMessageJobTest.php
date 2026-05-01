<?php

namespace Tests\Feature;

use App\Jobs\SendDiscordDirectMessage;
use App\Services\DiscordBot;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SendDiscordDirectMessageJobTest extends TestCase
{
    public function test_handle_no_ops_when_bot_not_configured(): void
    {
        config(['services.discord.bot_token' => '']);
        Http::fake();

        $job = new SendDiscordDirectMessage('123456789012345678', 'hello');
        $job->handle(app(DiscordBot::class));

        Http::assertNothingSent();
    }

    public function test_handle_skips_and_warns_for_non_snowflake_recipient(): void
    {
        config(['services.discord.bot_token' => 'fake-bot-token']);
        Http::fake();
        Log::spy();

        $job = new SendDiscordDirectMessage('not-a-snowflake', 'hi', [], 'ref-1');
        $job->handle(app(DiscordBot::class));

        Http::assertNothingSent();
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'SendDiscordDirectMessage skipped'));
    }

    public function test_handle_opens_dm_channel_then_posts_message(): void
    {
        config([
            'services.discord.bot_token' => 'fake-bot-token',
            'services.discord.api_base'  => 'https://discord.com/api/v10',
        ]);

        Http::fake([
            'discord.com/api/v10/users/@me/channels' => Http::response(['id' => 'CHAN-42'], 200),
            'discord.com/api/v10/channels/CHAN-42/messages' => Http::response([], 200),
        ]);

        $job = new SendDiscordDirectMessage('123456789012345678', 'hello world', [], 'ref-2');
        $job->handle(app(DiscordBot::class));

        Http::assertSentCount(2);
        Http::assertSent(fn ($req) => $req->url() === 'https://discord.com/api/v10/users/@me/channels');
        Http::assertSent(fn ($req) => $req->url() === 'https://discord.com/api/v10/channels/CHAN-42/messages');
    }
}
