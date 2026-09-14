<?php

namespace App\Concerns;

use Illuminate\Http\Client\Response;

/**
 * Discord answers a rate limit with the exact wait in `retry_after`
 * (seconds, fractional) and mirrors it in the `Retry-After` header.
 *
 * Letting a job's fixed backoff guess instead burns one of its three
 * attempts and usually comes back too early anyway, so both Discord jobs
 * release themselves for the interval Discord actually asked for. This
 * matters most on a roster-wide announcement: opening a DM channel is
 * the tightest limit the bot meets, and a broadcast hits it 20 times at
 * once.
 */
trait HandlesDiscordRateLimit
{
    protected function isRateLimited(?Response $response): bool
    {
        return $response?->status() === 429;
    }

    protected function releaseForRateLimit(Response $response): void
    {
        $seconds = $response->json('retry_after')
            ?? $response->header('Retry-After')
            ?? 5;

        $this->release(max(1, (int) ceil((float) $seconds)));
    }
}
