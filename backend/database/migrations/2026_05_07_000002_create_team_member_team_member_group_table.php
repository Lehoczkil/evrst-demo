<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot for TeamMember <-> TeamMemberGroup with assignment metadata
 * (primary flag, optional title override, role history). Has its own
 * id PK so the same person can hold the same role across multiple
 * non-overlapping time spans (e.g. Captain 2023, Captain again 2025).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_team_member_group', function (Blueprint $table) {
            $table->id();

            $table->foreignId('team_member_id')
                ->constrained('team_members')->cascadeOnDelete();
            $table->foreignId('team_member_group_id')
                ->constrained('team_member_groups')->cascadeOnDelete();

            // At most one primary assignment per member; enforced in
            // app code (TeamMember::setPrimaryGroup()).
            $table->boolean('is_primary')->default(false);

            // Optional per-assignment title override ({en, hu}).
            $table->json('title')->nullable();

            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();

            // Sort the member within the group's roster.
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->index(['team_member_id', 'is_primary']);
            $table->index(['team_member_group_id', 'position']);
            $table->index('ended_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_team_member_group');
    }
};
