<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `discord_id` was holding Discord usernames (e.g. "e.1415") because no
 * snowflakes had been collected yet. Once we wire up the bot for direct
 * messages, `discord_id` must be the snowflake — `<@username>` mention
 * markup never resolves. Split the two:
 *
 *   discord_username  string, unique, nullable — the @handle
 *   discord_id        string, unique, nullable — the numeric snowflake
 *
 * The seeder writes the spreadsheet handles into `discord_username`
 * directly, so this migration only adds the column. Any pre-existing
 * dev rows with usernames in `discord_id` get wiped on the next
 * `db:seed --class=TeamSeeder` run.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->string('discord_username', 64)->nullable()->after('discord_nick');
            $table->unique('discord_username');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropUnique(['discord_username']);
            $table->dropColumn('discord_username');
        });
    }
};
