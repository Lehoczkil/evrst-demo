<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_member_groups', function (Blueprint $table) {
            $table->id();

            // Optional self-FK so departments can nest into squads.
            $table->foreignId('parent_id')->nullable()
                ->constrained('team_member_groups')->nullOnDelete();

            // Stable code-side identifier — survives renames so
            // permission grants and code lookups don't break.
            $table->string('slug', 64)->unique();

            // {en, hu} translation maps.
            $table->json('name');
            $table->json('description')->nullable();

            // 'leadership' | 'department' | 'squad' — drives org-chart
            // rendering and may earn permission grants later.
            $table->string('kind')->default('department');

            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_public')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_member_groups');
    }
};
