<?php

namespace App\Console\Commands;

use App\Models\TeamMember;
use App\Services\DiscordBot;
use Illuminate\Console\Command;

/**
 * Fill `team_members.discord_id` from the guild's own member list.
 *
 * The roster already carries `discord_username` for almost everyone, and
 * the snowflake is the one field the DM path cannot work without — so
 * transcribing 20-odd IDs by hand is exactly the job nobody should do
 * twice. Re-runnable: it only writes rows whose snowflake is missing or
 * has drifted, and `--dry-run` shows the whole plan first.
 */
class SyncDiscordIds extends Command
{
    protected $signature = 'discord:sync-ids
        {--dry-run : Report what would change without writing anything}
        {--guild= : Guild id to read, overriding DISCORD_GUILD_ID}
        {--nicks : Also refresh discord_nick from the server display name}';

    protected $description = 'Match team members to Discord accounts and store their snowflake IDs';

    public function handle(DiscordBot $bot): int
    {
        if (! $bot->isConfigured()) {
            $this->error('DISCORD_BOT_TOKEN is not set — there is nothing to ask.');

            return self::FAILURE;
        }

        $guildId = trim((string) ($this->option('guild') ?: config('services.discord.guild_id')));
        if ($guildId === '') {
            $this->error('No guild id. Set DISCORD_GUILD_ID in .env, or pass --guild=<id>.');

            return self::FAILURE;
        }

        $this->info("Reading members of guild {$guildId} …");
        $members = $bot->listGuildMembers($guildId);

        if ($members === null) {
            $this->error('Could not read the member list.');
            $this->line('The bot needs the "Server Members Intent" enabled under Bot in the Discord developer portal — sending DMs does not, so it is easy to miss.');

            return self::FAILURE;
        }

        $this->line(count($members) . ' Discord member(s) returned.');

        $byHandle = $this->indexByHandle($members);
        $dry = (bool) $this->option('dry-run');

        $rows = [];
        $updated = 0;
        $skipped = 0;
        $claimed = [];

        foreach (TeamMember::query()->whereNull('left_at')->orderBy('name')->get() as $member) {
            $handle = mb_strtolower(trim((string) $member->discord_username));
            $match = $handle !== '' ? ($byHandle[$handle] ?? null) : null;

            if (! $match) {
                $skipped++;
                $rows[] = [$member->name, $member->discord_username ?: '—', '—', 'no match'];
                continue;
            }

            $snowflake = (string) $match['user']['id'];

            // discord_id is unique. Two roster rows pointing at one
            // Discord account is a data problem to report, not an
            // integrity violation to crash on halfway through the loop.
            if (isset($claimed[$snowflake])) {
                $skipped++;
                $rows[] = [$member->name, $member->discord_username, $snowflake, 'clashes with ' . $claimed[$snowflake]];
                continue;
            }

            $heldElsewhere = TeamMember::withTrashed()
                ->where('discord_id', $snowflake)
                ->whereKeyNot($member->getKey())
                ->value('name');

            if ($heldElsewhere) {
                $skipped++;
                $rows[] = [$member->name, $member->discord_username, $snowflake, 'already on ' . $heldElsewhere];
                continue;
            }

            $claimed[$snowflake] = $member->name;

            $changes = [];
            if ((string) $member->discord_id !== $snowflake) {
                $member->discord_id = $snowflake;
                $changes[] = 'id';
            }

            $nick = $match['nick'] ?? ($match['user']['global_name'] ?? null);
            if ($this->option('nicks') && $nick && $member->discord_nick !== $nick) {
                $member->discord_nick = $nick;
                $changes[] = 'nick';
            }

            if ($changes === []) {
                $rows[] = [$member->name, $member->discord_username, $snowflake, 'up to date'];
                continue;
            }

            if (! $dry) {
                $member->save();
            }

            $updated++;
            $rows[] = [
                $member->name,
                $member->discord_username,
                $snowflake,
                ($dry ? 'would set ' : 'set ') . implode(' + ', $changes),
            ];
        }

        $this->newLine();
        $this->table(['Member', 'Handle', 'Snowflake', 'Result'], $rows);
        $this->line(($dry ? 'Would update ' : 'Updated ') . $updated . ' member(s); ' . $skipped . ' left alone.');

        if ($skipped > 0) {
            $this->comment('Unmatched rows need discord_username corrected on the Team member form, or the snowflake pasted in by hand.');
        }

        return self::SUCCESS;
    }

    /**
     * Index the guild by every name Discord might answer to, lowercased.
     * The roster stores whichever handle the member gave us, and Discord
     * has been through two naming schemes (`name#1234` and the current
     * unique usernames), so match on all of them.
     *
     * @param  array<int, array<string, mixed>>  $members
     * @return array<string, array<string, mixed>>
     */
    private function indexByHandle(array $members): array
    {
        $byHandle = [];

        foreach ($members as $member) {
            $user = $member['user'] ?? null;
            if (! is_array($user) || empty($user['id'])) {
                continue;
            }

            foreach ([$user['username'] ?? null, $user['global_name'] ?? null, $member['nick'] ?? null] as $handle) {
                $handle = is_string($handle) ? mb_strtolower(trim($handle)) : '';
                if ($handle === '') {
                    continue;
                }

                // First writer wins, and usernames are indexed first, so a
                // nickname collision can never repoint a match that was
                // made on a real username.
                $byHandle[$handle] ??= $member;
            }
        }

        return $byHandle;
    }
}
