<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes that the dashboard widgets, calendar, kanban, and the
 * application nav badge actually use. Each one is a hot-path query
 * that was running a full scan on the existing schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Kanban + tasks list default sort: WHERE status IN (...) ORDER BY position
            $table->index(['status', 'position'], 'tasks_status_position_idx');
            // Calendar + UpcomingSchedule: WHERE due_date BETWEEN ?
            $table->index('due_date', 'tasks_due_date_idx');
            // MyTasks widget: WHERE supervisor_id = ?
            $table->index('supervisor_id', 'tasks_supervisor_idx');
        });

        Schema::table('member_applications', function (Blueprint $table) {
            // Both list filter + nav-badge count: WHERE status = 'PENDING'
            $table->index(['status', 'created_at'], 'member_apps_status_created_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            // Lookups by subject (e.g. on a record's history view).
            $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_subject_when_idx');
        });

        // task_user already has a composite primary key on (task_id, user_id)
        // — no extra index needed for the supervisor + assignee joins.

        Schema::table('resources', function (Blueprint $table) {
            // CMS index pages all sort by position within a collection.
            $table->index(['collection_id', 'position'], 'resources_collection_position_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_status_position_idx');
            $table->dropIndex('tasks_due_date_idx');
            $table->dropIndex('tasks_supervisor_idx');
        });

        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropIndex('member_apps_status_created_idx');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex('activity_subject_when_idx');
        });

        Schema::table('resources', function (Blueprint $table) {
            $table->dropIndex('resources_collection_position_idx');
        });
    }
};
