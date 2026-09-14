<?php

namespace App\Jobs;

use App\Concerns\HandlesDiscordRateLimit;
use App\Services\DiscordBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DM a single Discord user via the bot.
 *
 * No-ops until DISCORD_BOT_TOKEN is set and the recipient has a
 * snowflake in `team_members.discord_id`. The channel webhook carries
 * every one of these messages as well, so a member with no snowflake
 * loses nothing but the private copy.
 */
class SendDiscordDirectMessage implements ShouldQueue
{
    use Dispatchable, HandlesDiscordRateLimit, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  string  $snowflake  Discord user snowflake (17–20 digits).
     * @param  string  $content    Plain text body.
     * @param  array<string, mixed>  $embed  Optional embed.
     * @param  string  $reference  Free-form identifier for failure logs.
     */
    public function __construct(
        public string $snowflake,
        public string $content,
        public array $embed = [],
        public string $reference = '',
    ) {
    }

    public function handle(DiscordBot $bot): void
    {
        if (! $bot->isConfigured()) return;
        if (! preg_match('/^\d{17,20}$/', $this->snowflake)) {
            Log::warning('SendDiscordDirectMessage skipped — non-snowflake recipient', [
                'reference' => $this->reference,
                'recipient' => $this->snowflake,
            ]);
            return;
        }

        $channel = $bot->openDirectMessageChannelResponse($this->snowflake);
        if (! $channel) return;

        if ($this->isRateLimited($channel)) {
            $this->releaseForRateLimit($channel);
            return;
        }

        // A failed open is the recipient's own setting — DMs closed, or
        // no server shared with the bot. Retrying cannot change either,
        // and the channel post already carried the message.
        if ($channel->failed()) return;

        $channelId = $channel->json('id');
        if (! $channelId) return;

        $response = $bot->sendMessage($channelId, $this->content, $this->embed);
        if (! $response) return;

        if ($this->isRateLimited($response)) {
            $this->releaseForRateLimit($response);
            return;
        }

        if ($response->failed()) {
            throw new \RuntimeException('Discord DM returned ' . $response->status() . ': ' . $response->body());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Discord DM failed' . ($this->reference ? ' (' . $this->reference . ')' : ''), [
            'error' => $e->getMessage(),
        ]);
    }
}
