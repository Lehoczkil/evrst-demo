<?php

use App\Models\ApplicationFormField;
use Database\Seeders\ApplicationFormSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Replace the join-us questions with the team's real Google Form
 * ("E.V.R.S.T. Tagfelvétel"), ported into ApplicationFormSeeder.
 *
 * Unlike 2026_09_09_000004, which bailed out when the table already had
 * rows, this one runs unconditionally — replacing what is there is the
 * whole point. The seeder is updateOrCreate on the key, so:
 *
 *  · a question that exists keeps its id and gets the new wording,
 *  · new questions are appended,
 *  · questions the form no longer asks are deactivated, not deleted, so
 *    answers already collected under them stay readable on the
 *    application they belong to (MemberApplicationForm renders an
 *    orphaned answer under its raw key).
 *
 * Nothing already submitted is touched: answers live in
 * member_applications.answers, keyed by field key, and this only rewrites
 * the questions.
 *
 * Wording edited in the panel after this ships WILL be overwritten if the
 * migration is ever re-run from scratch — it is a one-shot, and the panel
 * is the place to edit from here on.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new ApplicationFormSeeder())->run();
    }

    public function down(): void
    {
        // Reactivating the retired questions is the only reversible half;
        // the previous wording is not recoverable from here, and the
        // seeder that held it has moved on.
        ApplicationFormField::whereIn('key', ApplicationFormSeeder::RETIRED_KEYS)
            ->update(['is_active' => true]);
    }
};
