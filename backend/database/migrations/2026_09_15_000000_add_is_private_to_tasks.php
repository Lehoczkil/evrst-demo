<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A task an admin can keep off the board.
 *
 * Default false, so every task that already exists stays exactly as
 * visible as it was — this adds a way to hide one, it does not hide
 * anything retroactively.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_private')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('is_private');
        });
    }
};
