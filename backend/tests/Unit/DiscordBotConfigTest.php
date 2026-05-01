<?php

namespace Tests\Unit;

use App\Services\DiscordBot;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DiscordBotConfigTest extends TestCase
{
    public function test_is_configured_false_when_token_empty(): void
    {
        config(['services.discord.bot_token' => '']);

        $this->assertFalse((new DiscordBot())->isConfigured());
    }

    public function test_open_direct_message_channel_returns_null_without_token(): void
    {
        config(['services.discord.bot_token' => '']);
        Http::fake();

        $result = (new DiscordBot())->openDirectMessageChannel('123456789012345678');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_send_message_returns_null_without_token(): void
    {
        config(['services.discord.bot_token' => '']);
        Http::fake();

        $result = (new DiscordBot())->sendMessage('chan', 'hi');

        $this->assertNull($result);
        Http::assertNothingSent();
    }
}
