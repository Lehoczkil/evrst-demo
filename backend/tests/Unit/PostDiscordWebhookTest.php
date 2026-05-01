<?php

namespace Tests\Unit;

use App\Jobs\PostDiscordWebhook;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PostDiscordWebhookTest extends TestCase
{
    private const GLOBAL_URL = 'https://discord.com/api/webhooks/000/global';
    private const OVERRIDE_URL = 'https://discord.com/api/webhooks/111/override';

    public function test_posts_to_global_webhook_when_no_override(): void
    {
        config(['services.discord.webhook' => self::GLOBAL_URL]);
        Http::fake();

        (new PostDiscordWebhook('hi', ['title' => 'T'], 'ref'))->handle();

        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->url() === self::GLOBAL_URL);
    }

    public function test_per_call_override_takes_precedence_over_global(): void
    {
        config(['services.discord.webhook' => self::GLOBAL_URL]);
        Http::fake();

        (new PostDiscordWebhook('hi', ['title' => 'T'], 'ref', self::OVERRIDE_URL))->handle();

        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->url() === self::OVERRIDE_URL);
    }

    public function test_malformed_override_falls_back_to_global_and_warns(): void
    {
        config(['services.discord.webhook' => self::GLOBAL_URL]);
        Http::fake();
        Log::spy();

        (new PostDiscordWebhook('hi', ['title' => 'T'], 'ref', 'https://example.com/not-a-discord-webhook'))->handle();

        Http::assertSentCount(1);
        Http::assertSent(fn ($req) => $req->url() === self::GLOBAL_URL);
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'Discord webhook override looks malformed'));
    }

    public function test_no_request_when_global_empty_and_no_override(): void
    {
        config(['services.discord.webhook' => '']);
        Http::fake();

        (new PostDiscordWebhook('hi', ['title' => 'T'], 'ref'))->handle();

        Http::assertNothingSent();
    }
}
