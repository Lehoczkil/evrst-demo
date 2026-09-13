<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop `team_member_groups.description`.
 *
 * Declared in `$fillable` and cast to an array, and read by nothing: not
 * the panel (no input ever existed), not the public API
 * (`TeamController::groups` maps explicit fields), not the SPA. Every row
 * has held null since the table was created.
 *
 * The sibling column on `application_form_sections` looked the same and is
 * NOT dropped — that one the SPA actually renders, it was only missing its
 * input, which is now on the form.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_member_groups', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }

    public function down(): void
    {
        Schema::table('team_member_groups', function (Blueprint $table) {
            $table->json('description')->nullable();
        });
    }
};
