<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How much of themselves a member is giving the team, as its own fact.
 *
 * Deliberately not folded into a position, a group or `left_at`: someone
 * can be part-time in any role, and part-time is not a kind of leaving.
 * Default false — everyone starts full-time, and a row only becomes
 * part-time because someone said so.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->boolean('is_part_time')->default(false)->after('is_public');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropColumn('is_part_time');
        });
    }
};
