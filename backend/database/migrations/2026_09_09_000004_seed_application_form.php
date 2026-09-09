<?php

use Database\Seeders\ApplicationFormSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Populate the form on an environment that has already been seeded.
 *
 * db:seed runs exactly once per volume (the .seeded marker), so on the
 * deployed instance the seeder alone would never run and the public form
 * would render zero questions. This puts the questions there on the next
 * boot, from the same seeder class, and only when the table is empty — so
 * it can never overwrite a form someone has edited in the panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('application_form_fields')->exists()) {
            return;
        }

        (new ApplicationFormSeeder())->run();
    }

    public function down(): void
    {
        // The tables are dropped by the migration that created them.
    }
};
