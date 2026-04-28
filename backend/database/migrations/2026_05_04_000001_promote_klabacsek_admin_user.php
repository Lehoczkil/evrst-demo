<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Promote the seeded `admin@evrst.test` account to Klabacsek Bálint
 * with a private (non-EVRST) email, and ensure he has a TeamMember
 * row that points back at the user. Idempotent — safe to re-run on
 * environments that already have the rename + the linked member.
 */
return new class extends Migration
{
    private const TEAM_MEMBERS_COLLECTION_ID = '00338d38-b302-4653-bb4e-9a734f46470e';

    private const NEW_NAME    = 'Klabacsek Bálint';
    private const NEW_EMAIL   = 'klabacsek.balint@gmail.com';
    private const DISCORD     = 'balint_klabacsek';

    public function up(): void
    {
        $user = DB::table('users')->where('email', 'admin@evrst.test')->first();
        if ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'name'  => self::NEW_NAME,
                'email' => self::NEW_EMAIL,
                'updated_at' => now(),
            ]);
        } else {
            // Already renamed on a previous run — find by the new email.
            $user = DB::table('users')->where('email', self::NEW_EMAIL)->first();
        }
        if (! $user) return;

        // Ensure exactly one TeamMember points at this user.
        $existing = DB::table('resources')
            ->where('collection_id', self::TEAM_MEMBERS_COLLECTION_ID)
            ->whereRaw("json_extract(payload, '$.user.id') = ?", [$user->id])
            ->first();

        $payload = json_encode([
            'name'           => self::NEW_NAME,
            'email'          => self::NEW_EMAIL,
            'private_email'  => self::NEW_EMAIL,
            'discord'        => self::DISCORD,
            'user'           => ['id' => $user->id, 'name' => self::NEW_NAME, 'email' => self::NEW_EMAIL],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($existing) {
            // Merge — don't blow away positions / photo / degrees a human
            // may have edited; just refresh the user / contact metadata.
            $current = json_decode($existing->payload ?? '[]', true) ?: [];
            $current['name']          = self::NEW_NAME;
            $current['email']         = self::NEW_EMAIL;
            $current['private_email'] = self::NEW_EMAIL;
            $current['discord']       = self::DISCORD;
            $current['user']          = ['id' => $user->id, 'name' => self::NEW_NAME, 'email' => self::NEW_EMAIL];
            DB::table('resources')->where('id', $existing->id)->update([
                'payload' => json_encode($current, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
            return;
        }

        DB::table('resources')->insert([
            'id'            => (string) Str::uuid(),
            'collection_id' => self::TEAM_MEMBERS_COLLECTION_ID,
            'payload'       => $payload,
            'position'      => 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        // Reversing the rename safely is risky (the admin may have logged
        // in and changed the email manually). Leave the data alone.
    }
};
