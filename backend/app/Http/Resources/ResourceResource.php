<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;

class ResourceResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Payload keys whose value, when set, should be resolved to a public URL.
     * Filament file uploads store relative disk paths; the SPA expects URLs.
     */
    private const FILE_KEYS = ['photo', 'image', 'logo'];

    /**
     * Locales the API knows how to serve. Anything outside this list is
     * treated as opaque data when scanning translation maps.
     */
    private const LOCALES = ['en', 'hu'];

    /**
     * Payload keys that must never reach a browser.
     *
     * `GET /api/resource` is unauthenticated and, with no `collectionId`,
     * returns every row in the store — and this transformer used to emit
     * `payload` verbatim. Two of the admin forms write secrets into it:
     * `AboutProject` stores a `discord_webhook_url` (anyone holding it can
     * post to the team's channel) and `Mentor` stores an `email` the site
     * itself only ever renders the domain of.
     *
     * A deny-list rather than a per-collection allow-list because the
     * `pages` and `views` payloads are free-form: the home hero/rocket copy
     * is walked by dotted path in the SPA, and a page's shape is whatever
     * the editor made it. Add to this list whenever a form gains a field
     * the public has no business seeing.
     */
    private const PRIVATE_KEYS = [
        'discord_webhook_url',
        'webhook_url',
        'email',
        'private_email',
        'email_private',
        'password',
        'secret',
        'token',
        'api_key',
    ];

    public function toArray(Request $request): array
    {
        $includeObjects = in_array(
            'objects',
            array_map('trim', explode(',', (string) $request->query('include', ''))),
            true,
        );

        $locale = app()->getLocale();

        $data = [
            'id' => $this->id,
            'collectionId' => $this->collection_id,
            'payload' => $this->localizePayload(
                $this->resolvePayloadFiles($this->redactPrivateKeys($this->payload)),
                $locale,
            ),
            'createdAt' => optional($this->created_at)->toISOString(),
            'updatedAt' => optional($this->updated_at)->toISOString(),
        ];

        if ($includeObjects && $this->relationLoaded('objects')) {
            $data['objects'] = $this->objects->map(fn ($object) => [
                'id' => $object->id,
                'key' => $object->key,
                'url' => $object->url,
            ])->all();
        } elseif ($includeObjects) {
            $data['objects'] = [];
        }

        return $data;
    }

    public static function collection($resource): ResourceCollection
    {
        return parent::collection($resource);
    }

    /**
     * Strip every PRIVATE_KEYS entry, at every depth — a payload is an
     * arbitrary JSON tree, so a secret can sit anywhere in it.
     *
     * `email` is the one denied key with a genuinely public half: the
     * mentors band renders the domain of the address and nothing else.
     * It is replaced by `email_domain` so the band keeps working while
     * the address itself stops leaving the server.
     */
    private function redactPrivateKeys(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $key => $entry) {
            if (is_string($key) && in_array(strtolower($key), self::PRIVATE_KEYS, true)) {
                if ($key === 'email' && is_string($entry) && str_contains($entry, '@')) {
                    $result['email_domain'] = substr($entry, strrpos($entry, '@') + 1);
                }

                continue;
            }

            $result[$key] = $this->redactPrivateKeys($entry);
        }

        return $result;
    }

    private function resolvePayloadFiles(?array $payload): ?array
    {
        if (! is_array($payload)) {
            return $payload;
        }

        foreach (self::FILE_KEYS as $key) {
            if (! isset($payload[$key]) || ! is_string($payload[$key])) {
                continue;
            }
            $payload[$key] = $this->toPublicUrl($payload[$key]);
        }

        return $payload;
    }

    private function toPublicUrl(string $value): string
    {
        if (preg_match('#^(https?:|data:|/storage/)#i', $value) === 1) {
            return $value;
        }

        try {
            return Storage::disk('public')->url(ltrim($value, '/'));
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Recursively flatten translation maps to a single locale string.
     * A translation map is an associative array whose keys are all
     * supported locale codes (a subset of self::LOCALES). Anything else
     * is walked through unchanged.
     */
    private function localizePayload(mixed $value, string $locale): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($this->isTranslationMap($value)) {
            return $value[$locale]
                ?? $value['en']
                ?? $value['hu']
                ?? array_values($value)[0]
                ?? null;
        }

        $result = [];
        foreach ($value as $key => $entry) {
            $result[$key] = $this->localizePayload($entry, $locale);
        }
        return $result;
    }

    private function isTranslationMap(array $value): bool
    {
        if ($value === []) return false;
        foreach ($value as $key => $entry) {
            if (! is_string($key)) return false;
            if (! in_array($key, self::LOCALES, true)) return false;
            if (! is_string($entry) && $entry !== null) return false;
        }
        return true;
    }
}
