<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_group_id')->nullable()
                ->constrained('contact_groups')->nullOnDelete();
            $table->string('name', 120);
            $table->string('phone', 32)->nullable();
            $table->string('email', 180)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('email');
            $table->index(['contact_group_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
