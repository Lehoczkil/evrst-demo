<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repoint the two foreign keys that pointed at the old CMS-style
 * TeamMember (a UUID row in `resources`) to the new bigint-PK
 * `team_members` table.
 *
 * Local-dev-only repoint: we drop+recreate the columns rather than
 * mapping UUID values to bigints, because no production data exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_stocks', function (Blueprint $table) {
            $table->dropForeign(['owner_team_member_id']);
            $table->dropIndex(['owner_team_member_id']);
            $table->dropColumn('owner_team_member_id');
        });
        Schema::table('item_stocks', function (Blueprint $table) {
            $table->foreignId('owner_team_member_id')->nullable()
                ->constrained('team_members')->nullOnDelete();
            $table->index('owner_team_member_id');
        });

        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropColumn('team_member_id');
        });
        Schema::table('member_applications', function (Blueprint $table) {
            $table->foreignId('team_member_id')->nullable()
                ->constrained('team_members')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropForeign(['team_member_id']);
            $table->dropColumn('team_member_id');
        });
        Schema::table('member_applications', function (Blueprint $table) {
            $table->uuid('team_member_id')->nullable();
        });

        Schema::table('item_stocks', function (Blueprint $table) {
            $table->dropForeign(['owner_team_member_id']);
            $table->dropIndex(['owner_team_member_id']);
            $table->dropColumn('owner_team_member_id');
        });
        Schema::table('item_stocks', function (Blueprint $table) {
            $table->foreignUuid('owner_team_member_id')->nullable()
                ->constrained('resources')->nullOnDelete();
            $table->index('owner_team_member_id');
        });
    }
};
