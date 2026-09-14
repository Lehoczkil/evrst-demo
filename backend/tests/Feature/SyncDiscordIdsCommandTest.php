<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * `discord:sync-ids` is how 20-odd snowflakes get into the roster without
 * anyone transcribing them. The rules that matter are the ones that stop
 * it doing damage: it matches on the handle already on file, it never
 * points two roster rows at one Discord account (discord_id is unique),
 * and --dry-run writes nothing.
 */
class SyncDiscordIdsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config([
            'services.discord.bot_token' => 'fake-bot-token',
            'services.discord.api_base' => 'https://discord.com/api/v10',
            'services.discord.guild_id' => '42',
        ]);
    }

    public function test_it_fills_snowflakes_by_matching_the_stored_handle(): void
    {
        $matched = TeamMember::create(['name' => 'Matched Member', 'discord_username' => 'e.1415']);
        $unmatched = TeamMember::create(['name' => 'Unknown Member', 'discord_username' => 'nobody.here']);

        $this->fakeGuild([
            $this->guildMember('100000000000000001', 'e.1415', nick: 'Pi'),
            $this->guildMember('100000000000000002', 'someone.else'),
        ]);

        $this->artisan('discord:sync-ids')->assertSuccessful();

        $this->assertSame('100000000000000001', $matched->fresh()->discord_id);
        $this->assertNull($unmatched->fresh()->discord_id);
        // Nicks are opt-in — the roster's own nickname is not overwritten
        // by a server display name unless asked for.
        $this->assertNull($matched->fresh()->discord_nick);
    }

    public function test_nicks_flag_refreshes_the_server_display_name(): void
    {
        $member = TeamMember::create(['name' => 'Matched Member', 'discord_username' => 'e.1415']);
        $this->fakeGuild([$this->guildMember('100000000000000001', 'e.1415', nick: 'Pi')]);

        $this->artisan('discord:sync-ids', ['--nicks' => true])->assertSuccessful();

        $this->assertSame('Pi', $member->fresh()->discord_nick);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $member = TeamMember::create(['name' => 'Matched Member', 'discord_username' => 'e.1415']);
        $this->fakeGuild([$this->guildMember('100000000000000001', 'e.1415')]);

        $this->artisan('discord:sync-ids', ['--dry-run' => true])->assertSuccessful();

        $this->assertNull($member->fresh()->discord_id);
    }

    public function test_it_refuses_to_give_one_discord_account_to_two_members(): void
    {
        $first = TeamMember::create(['name' => 'Aaa First', 'discord_username' => 'shared.handle']);
        $second = TeamMember::create(['name' => 'Bbb Second', 'discord_username' => 'Shared.Handle']);

        $this->fakeGuild([$this->guildMember('100000000000000001', 'shared.handle')]);

        $this->artisan('discord:sync-ids')->assertSuccessful();

        $this->assertSame('100000000000000001', $first->fresh()->discord_id);
        $this->assertNull($second->fresh()->discord_id);
    }

    public function test_departed_members_are_left_alone(): void
    {
        $left = TeamMember::create([
            'name' => 'Gone Member',
            'discord_username' => 'e.1415',
            'left_at' => now()->subYear(),
        ]);

        $this->fakeGuild([$this->guildMember('100000000000000001', 'e.1415')]);

        $this->artisan('discord:sync-ids')->assertSuccessful();

        $this->assertNull($left->fresh()->discord_id);
    }

    public function test_it_explains_the_missing_privileged_intent(): void
    {
        TeamMember::create(['name' => 'Matched Member', 'discord_username' => 'e.1415']);
        Http::fake(['discord.com/api/v10/guilds/*' => Http::response(['message' => 'Missing Access'], 403)]);

        $this->artisan('discord:sync-ids')
            ->expectsOutputToContain('Server Members Intent')
            ->assertFailed();
    }

    public function test_it_stops_before_the_network_when_the_bot_is_not_configured(): void
    {
        config(['services.discord.bot_token' => '']);
        Http::fake();

        $this->artisan('discord:sync-ids')->assertFailed();

        Http::assertNothingSent();
    }

    /** @param array<int, array<string, mixed>> $members */
    private function fakeGuild(array $members): void
    {
        Http::fake(['discord.com/api/v10/guilds/*' => Http::response($members, 200)]);
    }

    /** @return array<string, mixed> */
    private function guildMember(string $id, string $username, ?string $nick = null): array
    {
        return [
            'nick' => $nick,
            'user' => ['id' => $id, 'username' => $username, 'global_name' => null],
        ];
    }
}
