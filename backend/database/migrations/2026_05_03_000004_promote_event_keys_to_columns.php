<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Promote the hot Event payload keys (start_at / end_at / status) to
 * real, indexable columns on the shared resources table. Other CMS
 * collection types (sponsors, projects, …) leave them null.
 *
 * Existing rows are backfilled from `payload` so the live API stays
 * correct without a code change. New writes go through the Event
 * model and dual-write to both columns and JSON for one transition
 * release.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable()->after('payload');
            $table->dateTime('end_at')->nullable()->after('start_at');
            // 'status' would clash with status fields on other models /
            // future migrations, so namespace it.
            $table->string('event_status', 32)->nullable()->after('end_at');
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->index(['event_status', 'start_at'], 'resources_event_status_start_idx');
            $table->index('start_at', 'resources_start_at_idx');
            $table->index('end_at',   'resources_end_at_idx');
        });

        // Backfill from JSON payload — runs once, idempotent.
        DB::table('resources')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $r) {
                    $payload = json_decode($r->payload ?? '[]', true) ?: [];
                    $start = $payload['start_at'] ?? null;
                    $end = $payload['end_at'] ?? null;
                    $status = $payload['status'] ?? null;
                    if (! $start && ! $end && ! $status) continue;

                    DB::table('resources')->where('id', $r->id)->update([
                        'start_at' => $start ?: null,
                        'end_at' => $end ?: null,
                        'event_status' => is_string($status) ? $status : null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('resources', function (Blueprint $table) {
            $table->dropIndex('resources_event_status_start_idx');
            $table->dropIndex('resources_start_at_idx');
            $table->dropIndex('resources_end_at_idx');
        });
        Schema::table('resources', function (Blueprint $table) {
            $table->dropColumn(['start_at', 'end_at', 'event_status']);
        });
    }
};
