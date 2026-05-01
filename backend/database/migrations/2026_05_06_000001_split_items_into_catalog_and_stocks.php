<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Split the original single-table inventory into two:
 *   items        — catalog (just id + name)
 *   item_stocks  — one row per (item, venue, optional owner) with a quantity
 *
 * Keeps the items.id PK so existing rows survive; just drops the inventory
 * columns and moves them onto item_stocks.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            $table->foreignUuid('owner_team_member_id')
                ->nullable()
                ->constrained('resources')
                ->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index(['item_id', 'venue_id']);
            $table->index('owner_team_member_id');
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['venue_id']);
            $table->dropForeign(['owner_team_member_id']);
            $table->dropIndex(['venue_id']);
            $table->dropIndex(['owner_team_member_id']);
            $table->dropColumn(['quantity', 'venue_id', 'owner_team_member_id']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('venue_id')->nullable()->constrained('venues')->nullOnDelete();
            $table->foreignUuid('owner_team_member_id')
                ->nullable()
                ->constrained('resources')
                ->nullOnDelete();
        });

        Schema::dropIfExists('item_stocks');
    }
};
