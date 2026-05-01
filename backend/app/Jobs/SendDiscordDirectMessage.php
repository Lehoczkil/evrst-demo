<?php

namespace App\Jobs;

use App\Services\DiscordBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DM a single Discord user via the bot. No-op until the bot token is
 * populated and the recipient has a snowflake — the existing channel
 * webhook keeps carrying notifications until then.
 */
class SendDiscordDirectMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $channelId = $bot->openDirectMessageChannel($this->snowflake);
        if (! $channelId) return;

        $response = $bot->sendMessage($channelId, $this->content, $this->embed);
        if ($response && $response->failed()) {
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
