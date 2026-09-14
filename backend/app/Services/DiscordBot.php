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
        $response = $this->openDirectMessageChannelResponse($snowflake);

        return $response?->successful() ? $response->json('id') : null;
    }

    /**
     * The raw response behind {@see openDirectMessageChannel()}, so a
     * caller that can retry — the DM job — can tell a 429 (worth waiting
     * for) from a 403 because the recipient has DMs closed or shares no
     * server with the bot (retrying will never help).
     *
     * A 429 is not logged here: the job releases itself and comes back.
     */
    public function openDirectMessageChannelResponse(string $snowflake): ?Response
    {
        if (! $this->isConfigured()) return null;

        $response = $this->client()->post('/users/@me/channels', [
            'recipient_id' => $snowflake,
        ]);

        if ($response->failed() && $response->status() !== 429) {
            Log::warning('Discord DM channel open failed', [
                'snowflake' => $snowflake,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response;
    }

    /**
     * Every member of a guild, paginated 1000 at a time.
     *
     * Requires the **Server Members** privileged intent to be enabled for
     * the application in the Discord developer portal — without it this
     * endpoint answers 403 no matter what permissions the bot holds in
     * the server. Sending DMs needs no intent, so this is the one reason
     * to turn it on.
     *
     * @return array<int, array<string, mixed>>|null  null when the bot isn't configured or the call failed
     */
    public function listGuildMembers(string $guildId, int $max = 5000): ?array
    {
        if (! $this->isConfigured()) return null;

        $members = [];
        $after = '0';

        while (count($members) < $max) {
            $response = $this->client()->get("/guilds/{$guildId}/members", [
                'limit' => 1000,
                'after' => $after,
            ]);

            if ($response->failed()) {
                Log::warning('Discord guild member list failed', [
                    'guild' => $guildId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $page = $response->json();
            if (! is_array($page) || $page === []) break;

            foreach ($page as $member) {
                $members[] = $member;
            }

            // Discord pages by "everyone after this snowflake"; a short
            // page is the last one.
            if (count($page) < 1000) break;

            $after = (string) ($page[array_key_last($page)]['user']['id'] ?? '');
            if ($after === '') break;
        }

        return $members;
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
