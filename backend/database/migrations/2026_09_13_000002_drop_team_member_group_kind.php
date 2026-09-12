<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop `team_member_groups.kind`.
 *
 * It offered leadership / department / squad and nothing anywhere read it:
 * not the panel, not the public site, not the API consumer. The seeder
 * wrote 'department' for every row. A required dropdown with three options
 * and no consequence is a decision asked of an admin for nothing.
 *
 * Reversible — the column comes back with its old default, though the
 * values it held (all 'department') do not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_member_groups', function (Blueprint $table) {
            $table->dropColumn('kind');
        });
    }

    public function down(): void
    {
        Schema::table('team_member_groups', function (Blueprint $table) {
            $table->string('kind')->default('department');
        });
    }
};
