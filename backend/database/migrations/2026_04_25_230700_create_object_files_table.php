<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('object_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('resource_id')
                ->constrained('resources')
                ->cascadeOnDelete();
            $table->string('key')->nullable();
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['resource_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('object_files');
    }
};
