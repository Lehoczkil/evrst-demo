<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wrapper for the Discord Bot REST API. Used by SendDiscordDirectMessage
 * to DM team members by snowflake.
 *
 * The bot is not yet provisioned — until DISCORD_BOT_TOKEN is populated
 * and members supply real snowflake IDs, every method here no-ops and
 * returns null. Callers must tolerate a null return so the rest of the
 * notification pipeline (database + email + channel webhook) keeps
 * working in the meantime.
 */
class DiscordBot
{
    public function isConfigured(): bool
    {
        return filled(config('services.discord.bot_token'));
    }

    /**
     * Open (or fetch) the DM channel between the bot and `$snowflake`.
     * Returns the channel id, or null if the bot isn't configured / the
     * user has DMs disabled / no mutual server.
     */
    public function openDirectMessageChannel(string $snowflake): ?string
    {
        if (! $this->isConfigured()) return null;

        $response = $this->client()->post('/users/@me/channels', [
            'recipient_id' => $snowflake,
        ]);

        if ($response->failed()) {
            Log::warning('Discord DM channel open failed', [
                'snowflake' => $snowflake,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        return $response->json('id');
    }

    /**
     * Send a message to an open DM channel. Returns the API response
     * for caller-side inspection, or null when the bot isn't configured.
     *
     * @param  array<string, mixed>  $embed  Same shape as PostDiscordWebhook's embed.
     */
    public function sendMessage(string $channelId, string $content, array $embed = []): ?Response
    {
        if (! $this->isConfigured()) return null;

        $payload = ['content' => $content];
        if ($embed !== []) {
            $payload['embeds'] = [array_filter($embed, fn ($v) => $v !== null && $v !== '')];
        }

        return $this->client()->post("/channels/{$channelId}/messages", $payload);
    }

    private function client()
    {
        $base = rtrim((string) config('services.discord.api_base'), '/');
        return Http::baseUrl($base)
            ->withHeaders([
                'Authorization' => 'Bot ' . config('services.discord.bot_token'),
                'User-Agent' => 'EVRST-Bot (https://evrst.hu, 1.0)',
            ])
            ->asJson()
            ->timeout(10);
    }
}
