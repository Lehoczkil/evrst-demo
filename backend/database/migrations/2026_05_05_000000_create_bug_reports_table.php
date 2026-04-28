<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description');
            // open · triaging · in_progress · resolved · closed · wont_fix
            $table->string('status', 24)->default('open');
            // low · medium · high · critical
            $table->string('severity', 16)->default('medium');
            // Page the bug was reported from (e.g. /admin/tasks/3/edit).
            $table->string('page_url', 500)->nullable();
            // browser / OS / viewport — captured from the SPA on submit.
            $table->json('environment')->nullable();
            $table->string('screenshot_path', 255)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('severity');
            $table->index(['status', 'severity']);
            $table->index('reporter_id');
            $table->index('assignee_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
