<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // image | file | link | note  — controls which fields render.
            $table->string('kind', 16);
            $table->string('title', 200);
            $table->text('body')->nullable();
            // For link proofs (incl. Onshape, GitHub, Drive, etc.).
            $table->string('link_url', 500)->nullable();
            // For image / file proofs (PNG / GLB / STL / PDF / etc.).
            $table->string('file_disk', 32)->nullable();
            $table->string('file_path', 255)->nullable();
            $table->string('file_mime', 96)->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->timestamps();
            $table->index(['task_id', 'created_at'], 'task_proofs_task_when_idx');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_proofs');
    }
};
