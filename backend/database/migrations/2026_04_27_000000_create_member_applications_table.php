<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email');
            $table->string('name');
            $table->string('university')->nullable();
            $table->string('education')->nullable();
            $table->string('faculty')->nullable();
            $table->text('why')->nullable();
            $table->string('hours')->nullable();
            $table->json('languages')->nullable();
            $table->string('department')->nullable();
            $table->text('tasks')->nullable();
            $table->text('skills')->nullable();
            $table->string('status')->default('PENDING');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('team_member_id')->nullable();
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_applications');
    }
};
