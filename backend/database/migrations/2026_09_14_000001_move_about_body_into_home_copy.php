<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fold the About view's body into the home copy as the mission's first
 * paragraph.
 *
 * The two paragraphs that open the mission section were edited on two
 * different admin screens — the first under About → Content, the second
 * under Site → Home texts — which is impossible to guess from the page
 * itself. The About view's title fields were rendered nowhere at all: the
 * SPA has one route, so there is no About page for them to head.
 *
 * The old row is left in place rather than deleted. It is the only copy of
 * this text outside a backup, the SPA no longer reads it, and an unused
 * `views` row costs nothing.
 */
return new class extends Migration
{
    private const ABOUT_VIEW_ID = '90116104-aefd-4240-8e0d-8887668e21a0';

    private const HOME_COPY_ID = 'c1d0e9a4-6f3b-4a21-9b7e-2f5a8c0d4e11';

    private const VIEWS_COLLECTION_ID = 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa';

    public function up(): void
    {
        $about = DB::table('resources')->where('id', self::ABOUT_VIEW_ID)->first();
        if (! $about) {
            return;
        }

        $body = json_decode($about->payload ?? '{}', true)['content'] ?? null;
        if (! $body) {
            return;
        }

        // A very old row may hold a bare string instead of an {en, hu} map.
        $body = is_array($body) ? $body : ['en' => (string) $body, 'hu' => ''];
        $body = array_filter([
            'en' => trim((string) ($body['en'] ?? '')),
            'hu' => trim((string) ($body['hu'] ?? '')),
        ]);

        if ($body === []) {
            return;
        }

        $home = DB::table('resources')->where('id', self::HOME_COPY_ID)->first();
        $payload = $home ? (json_decode($home->payload ?? '{}', true) ?: []) : [];

        // Never overwrite: if someone has already typed a first paragraph
        // into the home copy, that is the newer text.
        if (! empty($payload['manifesto']['first'])) {
            return;
        }

        $payload['name'] = $payload['name'] ?? 'home-copy';
        $payload['manifesto']['first'] = $body;

        if ($home) {
            DB::table('resources')->where('id', self::HOME_COPY_ID)->update([
                'payload' => json_encode($payload),
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('resources')->insert([
            'id' => self::HOME_COPY_ID,
            'collection_id' => self::VIEWS_COLLECTION_ID,
            'payload' => json_encode($payload),
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $home = DB::table('resources')->where('id', self::HOME_COPY_ID)->first();
        if (! $home) {
            return;
        }

        $payload = json_decode($home->payload ?? '{}', true) ?: [];
        unset($payload['manifesto']['first']);

        DB::table('resources')->where('id', self::HOME_COPY_ID)->update([
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);
    }
};
