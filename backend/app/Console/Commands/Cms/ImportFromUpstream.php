<?php

namespace App\Console\Commands\Cms;

use App\Models\Collection;
use App\Models\ObjectFile;
use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-off importer that mirrors the legacy spacelab CMS API into our
 * local resources/object_files tables. Run with --only=rocket to
 * import just the home record + 3D model, or with no flags to sync
 * every known collection.
 */
class ImportFromUpstream extends Command
{
    protected $signature = 'cms:import {--base=http://188.187.122.34:41483} {--only=*}';

    protected $description = 'Mirror collections from the legacy CMS API into local DB + storage';

    private const HOME_PAGE_ID = 'f8e49c86-d46a-4720-8f26-3d01499b13c4';
    private const ABOUT_VIEW_ID = '90116104-aefd-4240-8e0d-8887668e21a0';

    /**
     * Local collection slug => upstream collection UUID + storage subdir.
     */
    /**
     * Local collection slug => upstream collection UUID, storage subdir, and
     * an optional remap target — when set, records are upserted with the
     * remap collection_id (so we stay consistent with our seeded UUIDs even
     * though the legacy CMS used a different one).
     */
    private const COLLECTIONS = [
        'pages' => ['id' => 'ced793f7-414b-41a7-8693-1e94627227df', 'dir' => 'pages'],
        'events' => ['id' => '36b42185-3a49-43ee-ba79-5cc73075b0d2', 'dir' => 'events'],
        'team-member-groups' => [
            'id' => '6cb25c60-f6f1-4c91-8306-430ef4dc1ed3',
            'dir' => 'team-member-groups',
            'remap' => 'a4b4cb01-f2a2-4be6-9c39-2c01b6fb1c70',
        ],
        'team-members' => ['id' => '00338d38-b302-4653-bb4e-9a734f46470e', 'dir' => 'team-members'],
        'mentors' => ['id' => '8639b34c-3415-40cc-85d0-e5ac0eb8d456', 'dir' => 'mentors'],
        'sponsors' => ['id' => '8aadff44-5a0b-4d84-b570-324db3f11a94', 'dir' => 'sponsors'],
    ];

    public function handle(): int
    {
        $base = rtrim((string) $this->option('base'), '/');
        $only = (array) $this->option('only');

        $this->components->info("Importing from {$base}");

        if (empty($only) || in_array('rocket', $only, true) || in_array('home', $only, true)) {
            $this->importHome($base);
        }

        if (empty($only) || in_array('about', $only, true)) {
            $this->importAboutView($base);
        }

        foreach (self::COLLECTIONS as $slug => $info) {
            if (! empty($only) && ! in_array($slug, $only, true)) {
                continue;
            }
            $this->importCollection($base, $slug, $info['id'], $info['dir']);
        }

        $this->newLine();
        $this->components->info('Done.');
        return self::SUCCESS;
    }

    private function importHome(string $base): void
    {
        $this->components->task('home page + rocket', function () use ($base) {
            $resp = Http::timeout(30)->get("{$base}/resource/" . self::HOME_PAGE_ID, ['include' => 'objects']);
            if (! $resp->ok()) {
                $this->error("home page fetch failed: {$resp->status()}");
                return false;
            }
            $body = $resp->json();
            $this->upsertResource($body, dir: 'pages');
            return true;
        });
    }

    private function importAboutView(string $base): void
    {
        $this->components->task('about view', function () use ($base) {
            $resp = Http::timeout(30)->get("{$base}/resource/" . self::ABOUT_VIEW_ID);
            if (! $resp->ok()) {
                $this->error("about view fetch failed: {$resp->status()}");
                return false;
            }
            $body = $resp->json();
            // Upstream uses a different collection_id for views; remap to ours.
            $body['collectionId'] = 'f2a4ad4c-f5b8-4d7f-9c2c-9d4d6c0b3aaa';
            $this->upsertResource($body, dir: 'views', ensureCollection: ['name' => 'Views', 'slug' => 'views']);
            return true;
        });
    }

    private function importCollection(string $base, string $slug, string $collectionId, string $dir): void
    {
        $this->components->task("collection: {$slug}", function () use ($base, $slug, $collectionId, $dir) {
            $resp = Http::timeout(60)->get("{$base}/resource", [
                'collectionId' => $collectionId,
                'include' => 'objects',
            ]);
            if (! $resp->ok()) {
                $this->error("{$slug} fetch failed: {$resp->status()}");
                return false;
            }
            $records = $resp->json() ?? [];
            $count = 0;
            foreach ($records as $record) {
                if ($remap = self::COLLECTIONS[$slug]['remap'] ?? null) {
                    $record['collectionId'] = $remap;
                }
                $this->upsertResource($record, dir: $dir);
                $count++;
            }
            $this->line("  → {$count} record(s)");
            return true;
        });
    }

    /**
     * Upsert a single upstream record into local DB:
     *  - Ensure the collection exists locally.
     *  - Materialise file URLs in payload (logo) by downloading.
     *  - Mirror objects[] to ObjectFile rows on the public disk.
     *  - For team members, snapshot the linked group payload.
     */
    private function upsertResource(array $record, string $dir, ?array $ensureCollection = null): void
    {
        $id = $record['id'] ?? null;
        if (! $id) return;

        $collectionId = $record['collectionId'] ?? null;
        if (! $collectionId) return;

        if ($ensureCollection) {
            Collection::updateOrCreate(['id' => $collectionId], [
                'name' => $ensureCollection['name'],
                'slug' => $ensureCollection['slug'],
            ]);
        } elseif (! Collection::whereKey($collectionId)->exists()) {
            // Skip records whose collection we didn't seed and weren't told to materialise.
            return;
        }

        $payload = $record['payload'] ?? [];
        $payload = $this->materialiseFilePayload($payload, $dir);

        // Snapshot team-member group so the SPA's existing
        // member.payload.group.payload.name pattern keeps working.
        if (is_string($payload['group'] ?? null)) {
            $group = Resource::find($payload['group']);
            if ($group) {
                $payload['group'] = ['id' => $group->id, 'payload' => $group->payload];
            }
        }

        $resource = Resource::updateOrCreate(['id' => $id], [
            'collection_id' => $collectionId,
            'payload' => $payload,
        ]);

        $this->mirrorObjects($resource, $record['objects'] ?? [], $dir);
    }

    /**
     * Replace any presigned/external URLs in payload[logo|photo|image] with
     * a downloaded copy on the public disk.
     */
    private function materialiseFilePayload(array $payload, string $dir): array
    {
        foreach (['logo', 'photo', 'image'] as $key) {
            $value = $payload[$key] ?? null;
            if (! is_string($value) || $value === '') continue;
            if (! str_starts_with($value, 'http')) continue;

            $stored = $this->downloadToPublicDisk($value, $dir);
            if ($stored !== null) {
                $payload[$key] = $stored;
            }
        }
        return $payload;
    }

    private function mirrorObjects(Resource $resource, array $objects, string $dir): void
    {
        if (empty($objects)) return;

        foreach ($objects as $remote) {
            $url = $remote['url'] ?? null;
            $key = $remote['key'] ?? null;
            $fileName = $remote['fileName'] ?? null;
            if (! $url) continue;

            $stored = $this->downloadToPublicDisk($url, $dir, $fileName);
            if (! $stored) continue;

            ObjectFile::updateOrCreate([
                'resource_id' => $resource->id,
                'key' => $key,
            ], [
                'disk' => 'public',
                'path' => $stored,
                'mime_type' => null,
                'size' => null,
            ]);

            // Mirror into payload too, so the SPA's payload.{key} reader picks
            // it up alongside the legacy objects[] consumer.
            if ($key && in_array($key, ['photo', 'image', 'logo', 'rocket'], true)) {
                $payload = $resource->payload ?? [];
                $payload[$key] = $stored;
                $resource->payload = $payload;
                $resource->save();
            }
        }
    }

    /**
     * Stream a remote file to the public disk under {dir}/{hash}.{ext}.
     * Returns the stored relative path, or null on failure.
     */
    private function downloadToPublicDisk(string $url, string $dir, ?string $hintName = null): ?string
    {
        try {
            $resp = Http::timeout(120)->get($url);
            if (! $resp->ok()) return null;

            $ext = $this->guessExtension($url, $hintName, $resp->header('Content-Type'));
            $base = $hintName
                ? pathinfo($hintName, PATHINFO_FILENAME)
                : substr(sha1($url), 0, 12);
            $filename = Str::slug($base, '-') . ($ext ? ".{$ext}" : '');
            $path = trim($dir, '/') . '/' . $filename;

            Storage::disk('public')->put($path, $resp->body());
            return $path;
        } catch (\Throwable $e) {
            $this->warn("download failed: {$e->getMessage()}");
            return null;
        }
    }

    private function guessExtension(string $url, ?string $hintName, ?string $contentType): string
    {
        if ($hintName) {
            $ext = pathinfo($hintName, PATHINFO_EXTENSION);
            if ($ext) return strtolower($ext);
        }
        $path = parse_url($url, PHP_URL_PATH) ?: '';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext) return strtolower($ext);

        return match (true) {
            str_contains((string) $contentType, 'gltf-binary') => 'glb',
            str_contains((string) $contentType, 'png') => 'png',
            str_contains((string) $contentType, 'jpeg') => 'jpg',
            str_contains((string) $contentType, 'webp') => 'webp',
            str_contains((string) $contentType, 'svg') => 'svg',
            default => '',
        };
    }
}
