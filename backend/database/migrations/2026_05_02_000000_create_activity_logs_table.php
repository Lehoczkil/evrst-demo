<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event');                       // created | updated | deleted | accepted | rejected | …
            $table->string('subject_type');                // FQCN of the model
            $table->string('subject_id');                  // string to fit both int + uuid keys
            $table->string('subject_label')->nullable();   // human-readable snapshot
            $table->json('changes')->nullable();           // ['attribute' => ['from' => x, 'to' => y]]
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
