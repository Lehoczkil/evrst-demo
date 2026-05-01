<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();

            // Optional link to an authenticatable account. Nullable so we can
            // list alumni / external contributors who never had a panel
            // account; unique because one user owns at most one member row.
            $table->foreignId('user_id')->nullable()->unique()
                ->constrained('users')->nullOnDelete();

            $table->string('name', 120);
            $table->string('email', 180)->nullable()->unique();
            $table->string('email_private', 180)->nullable();
            $table->string('discord_nick', 64)->nullable();
            $table->string('discord_id', 32)->nullable()->unique();

            // {en, hu} translation map. JSON keeps the i18n shape used
            // elsewhere in the app and lets us add a third locale without
            // a migration.
            $table->json('degree')->nullable();
            $table->json('bio')->nullable();

            $table->string('photo_path')->nullable();

            $table->date('joined_at')->nullable();
            // Soft-retire: set when a member leaves. Distinct from
            // is_public so an active member can opt out of the website.
            $table->date('left_at')->nullable();
            $table->boolean('is_public')->default(true);

            // Manual sort within the public team page.
            $table->unsignedInteger('position')->default(0);

            // Escape hatch for one-off fields (T-shirt size, emergency
            // contact, etc.) — not queried against.
            $table->json('meta')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['left_at', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
