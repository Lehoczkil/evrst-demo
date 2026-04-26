<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')
                ->constrained('collections')
                ->cascadeOnDelete();
            $table->json('payload')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['collection_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
