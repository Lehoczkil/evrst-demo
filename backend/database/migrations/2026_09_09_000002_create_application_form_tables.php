<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public join-us form, as data.
 *
 * Every question used to live in the SPA: the field list in JoinUsPage's
 * FormState, the choices in three TypeScript constants, the labels in the
 * i18n files, and a matching hand-written rule set in the API controller.
 * Changing a single option meant a frontend deploy — which is how the
 * department list drifted away from the actual team_member_groups.
 *
 * Sections carry the numbered cards the form renders as; fields carry the
 * questions. Both are localised {en, hu} like the rest of the CMS models.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_form_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->json('title');
            $table->json('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('application_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')
                ->constrained('application_form_sections')
                ->cascadeOnDelete();
            // The key is the answers[] key and the API payload key, so it is
            // the one thing an edit must not casually change.
            $table->string('key', 64)->unique();
            $table->string('type', 32);
            $table->json('label');
            $table->json('help')->nullable();
            $table->json('placeholder')->nullable();
            /** Choice list for radio / select / checkbox: [{value, label:{en,hu}}]. */
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            /**
             * name + email are structural: the accept flow provisions a login
             * from them and every notification addresses them. They can be
             * relabelled and reordered, never deleted or re-keyed.
             */
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('max_length')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_form_fields');
        Schema::dropIfExists('application_form_sections');
    }
};
