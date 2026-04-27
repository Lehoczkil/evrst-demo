<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onshape_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            // Onshape document / workspace / element IDs — extracted from
            // a pasted share URL. Element id is optional; if absent we
            // open the document in its default tab.
            $table->string('document_id', 64);
            $table->string('workspace_id', 64);
            $table->string('element_id', 64)->nullable();
            // Raw share URL kept as a fallback in case Onshape changes
            // their URL shape, or the user pasted something we couldn't
            // fully parse.
            $table->string('share_url', 500)->nullable();
            $table->string('thumbnail_path', 255)->nullable();
            $table->timestamps();
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onshape_models');
    }
};
