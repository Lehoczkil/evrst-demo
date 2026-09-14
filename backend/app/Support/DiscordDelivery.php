<?php

namespace App\Support;

use App\Jobs\PostDiscordWebhook;
use App\Jobs\SendDiscordDirectMessage;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single exit for everything this app says to Discord.
 *
 * Two channels run side by side and neither replaces the other: the
 * shared channel webhook keeps the team's public log of what happened,
 * and the bot DMs the people the message is actually about. A recipient
 * with no snowflake still gets the channel post, so collecting IDs into
 * `team_members.discord_id` is an upgrade rather than a switch — nothing
 * stops working while the roster fills in.
 *
 * Payloads come from {@see DiscordPayloads} and carry two content lines:
 * `content` for the channel, where an @-mention is what tells a reader
 * the message concerns them, and `dm_content` for the DM, where the same
 * mention is noise because the delivery already addressed them.
 */
class DiscordDelivery
{
    /**
     * A message about one person: the channel post plus a DM to them.
     *
     * @param  array{content: string, dm_content?: string, channel?: bool, embed: array<string, mixed>, reference: string}  $payload
     */
    public static function toRecipient(User $user, array $payload, ?string $webhookUrl = null): void
    {
        // The channel post is gated on having *something* displayable —
        // without a handle it would just dump the person's real name into
        // a public channel, which the in-app notification already covers.
        if (DiscordPayloads::wantsDiscordPing($user)) {
            self::toChannel($payload, $webhookUrl);
        }

        // Note the DM below is NOT gated on `channel`: a private subject is
        // exactly the case where the private copy is the only one sent.

        self::dm($user, $payload);
    }

    /**
     * A message about everyone: one channel post, plus a DM to each
     * recipient who can receive one.
     *
     * @param  array{content: string, dm_content?: string, embed: array<string, mixed>, reference: string}  $payload
     * @param  iterable<int, User>  $recipients
     */
    public static function announce(array $payload, iterable $recipients = [], ?string $webhookUrl = null): void
    {
        self::toChannel($payload, $webhookUrl);

        // Staggered by a second each: nobody is waiting on a calendar
        // notification, and firing 20 DM-channel opens in one breath is
        // the one thing guaranteed to trip Discord's rate limiter.
        $slot = 0;
        foreach ($recipients as $user) {
            self::dm($user, $payload, $slot++);
        }
    }

    /**
     * Channel only — for events that belong to the team as a whole and
     * have no individual recipient (a new application, a CMS event).
     *
     * @param  array{content: string, channel?: bool, embed: array<string, mixed>, reference: string}  $payload
     */
    public static function toChannel(array $payload, ?string $webhookUrl = null): void
    {
        // A payload whose subject is private never reaches the shared
        // channel, whichever entry point asked. The builder decides, so no
        // call site can forget to.
        if (($payload['channel'] ?? true) === false) {
            return;
        }

        PostDiscordWebhook::dispatch(
            $payload['content'],
            $payload['embed'],
            $payload['reference'],
            $webhookUrl,
        )->afterResponse();
    }

    /**
     * Everyone who can receive a DM: on the roster, not departed, and
     * carrying a snowflake. A member without one is not an error — they
     * are covered by the channel post until someone fills the column in,
     * by hand on the Team member form or with `discord:sync-ids`.
     *
     * @return Collection<int, User>
     */
    public static function audience(?int $excludeUserId = null): Collection
    {
        return User::query()
            ->whereHas('teamMember', fn ($q) => $q
                ->whereNull('left_at')
                ->whereNotNull('discord_id'))
            ->when($excludeUserId !== null, fn ($q) => $q->whereKeyNot($excludeUserId))
            ->with('teamMember')
            ->get();
    }

    /**
     * @param  array{content: string, dm_content?: string, embed: array<string, mixed>, reference: string}  $payload
     */
    private static function dm(User $user, array $payload, int $slot = 0): void
    {
        $snowflake = DiscordPayloads::snowflakeFor($user);
        if (! $snowflake) {
            return;
        }

        $pending = SendDiscordDirectMessage::dispatch(
            $snowflake,
            $payload['dm_content'] ?? $payload['content'],
            $payload['embed'],
            $payload['reference'],
        )->afterResponse();

        if ($slot > 0) {
            $pending->delay(now()->addSeconds($slot));
        }
    }
}
