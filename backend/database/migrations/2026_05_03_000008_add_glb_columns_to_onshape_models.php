<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onshape_models', function (Blueprint $table) {
            $table->string('glb_disk', 32)->nullable()->after('thumbnail_path');
            $table->string('glb_path', 255)->nullable()->after('glb_disk');
            $table->unsignedInteger('glb_size')->nullable()->after('glb_path');
            $table->dateTime('glb_exported_at')->nullable()->after('glb_size');
            // 'idle' | 'queued' | 'running' | 'failed'
            $table->string('glb_status', 16)->nullable()->after('glb_exported_at');
            $table->string('glb_error', 500)->nullable()->after('glb_status');
        });
    }

    public function down(): void
    {
        Schema::table('onshape_models', function (Blueprint $table) {
            $table->dropColumn(['glb_disk', 'glb_path', 'glb_size', 'glb_exported_at', 'glb_status', 'glb_error']);
        });
    }
};
