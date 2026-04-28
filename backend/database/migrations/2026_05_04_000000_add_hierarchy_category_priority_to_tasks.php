<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            // Self-referential parent. Cascade-delete keeps subtask trees
            // tidy when a parent is removed.
            $table->foreignId('parent_task_id')
                ->nullable()
                ->after('created_by')
                ->constrained('tasks')
                ->nullOnDelete();
            $table->string('category', 32)->nullable()->after('parent_task_id');
            $table->string('priority', 16)->default('normal')->after('category');
            $table->index('parent_task_id', 'tasks_parent_idx');
            $table->index('category', 'tasks_category_idx');
            $table->index('priority', 'tasks_priority_idx');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_parent_idx');
            $table->dropIndex('tasks_category_idx');
            $table->dropIndex('tasks_priority_idx');
            $table->dropForeign(['parent_task_id']);
            $table->dropColumn(['parent_task_id', 'category', 'priority']);
        });
    }
};
