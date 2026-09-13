<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One screenshot per bug report was never enough.
 *
 * A bug is usually a sequence — the screen before, the error, the console
 * — and the reporter had to pick one. `screenshot_path` (a single string)
 * becomes `screenshots` (a JSON list of paths), which is what Filament's
 * `FileUpload::multiple()` stores.
 *
 * Existing rows carry their one path into a single-element list, so no
 * evidence already attached to a report is lost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            $table->json('screenshots')->nullable()->after('page_url');
        });

        DB::table('bug_reports')
            ->whereNotNull('screenshot_path')
            ->where('screenshot_path', '!=', '')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('bug_reports')
                    ->where('id', $row->id)
                    ->update(['screenshots' => json_encode([$row->screenshot_path])]);
            });

        Schema::table('bug_reports', function (Blueprint $table) {
            $table->dropColumn('screenshot_path');
        });
    }

    public function down(): void
    {
        Schema::table('bug_reports', function (Blueprint $table) {
            $table->string('screenshot_path', 255)->nullable()->after('page_url');
        });

        // Only the first survives the round trip — the column holds one.
        DB::table('bug_reports')->whereNotNull('screenshots')->orderBy('id')->each(function ($row) {
            $paths = json_decode((string) $row->screenshots, true);

            if (is_array($paths) && $paths !== []) {
                DB::table('bug_reports')
                    ->where('id', $row->id)
                    ->update(['screenshot_path' => $paths[0]]);
            }
        });

        Schema::table('bug_reports', function (Blueprint $table) {
            $table->dropColumn('screenshots');
        });
    }
};
