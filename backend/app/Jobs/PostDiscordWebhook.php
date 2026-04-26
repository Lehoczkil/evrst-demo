<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Single, generic Discord webhook dispatch. Replaces the ad-hoc
 * SendDiscordWebhook + SendDiscordEventWebhook duplication. Pass an
 * embed shape; the job posts it and silently no-ops when
 * DISCORD_WEBHOOK_URL is empty (dev-friendly).
 */
class PostDiscordWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  string  $content   Plaintext message above the embed (e.g. "📅 New event").
     * @param  array<string, mixed>  $embed  Discord embed shape — keys: title, description, url, color (int), fields (array), timestamp.
     * @param  string  $reference  Free-form identifier used in failure logs.
     */
    public function __construct(
        public string $content,
        public array $embed,
        public string $reference = '',
    ) {
    }

    public function handle(): void
    {
        $webhook = config('services.discord.webhook');
        if (! $webhook) return;

        $payload = [
            'content' => $this->content,
            'embeds' => [array_filter($this->embed, fn ($v) => $v !== null && $v !== '')],
        ];

        $response = Http::asJson()->timeout(10)->post($webhook, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Discord webhook returned ' . $response->status() . ': ' . $response->body());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('Discord webhook failed' . ($this->reference ? ' (' . $this->reference . ')' : ''), [
            'error' => $e->getMessage(),
        ]);
    }
}
