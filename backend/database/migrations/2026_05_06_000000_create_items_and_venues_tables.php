<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('quantity')->default(1);
            $table->foreignId('venue_id')->constrained('venues')->restrictOnDelete();
            // Team members live in the polymorphic `resources` table with
            // a UUID primary key. Nullable: only set when venue == private.
            $table->foreignUuid('owner_team_member_id')
                ->nullable()
                ->constrained('resources')
                ->nullOnDelete();
            $table->timestamps();

            $table->index('venue_id');
            $table->index('owner_team_member_id');
        });

        DB::table('venues')->insert([
            ['key' => 'office',  'name' => 'Office',  'created_at' => now(), 'updated_at' => now()],
            ['key' => 'private', 'name' => 'Private', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
        Schema::dropIfExists('venues');
    }
};
