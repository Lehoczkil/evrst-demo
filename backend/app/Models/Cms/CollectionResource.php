<?php

namespace App\Models\Cms;

use App\Models\Resource;

/**
 * Resource scoped to a single collection.
 *
 * Subclasses set static::$collectionId to the collection UUID. Reads on the
 * scoped model are filtered to that collection automatically and writes set
 * collection_id when missing.
 */
abstract class CollectionResource extends Resource
{
    protected static string $collectionId = '';

    protected $table = 'resources';

    /**
     * Subclasses expose virtual attributes (title_en, title_hu, start_at,
     * positions_ids, …) that aren't in the parent Resource's $fillable.
     * Filament's update flow goes through `fill()` which respects
     * `$fillable` and silently drops anything missing — that's how event
     * forms ended up writing payload=null. Open the gate here so fill()
     * dispatches every form key through the Attribute setters.
     *
     * Both lists must be reset, because Eloquent's isFillable() checks
     * $fillable first; the parent Resource explicitly enumerates
     * ['id', 'collection_id', 'payload', 'position'] which would
     * otherwise still win.
     */
    protected $fillable = [];
    protected $guarded = [];

    protected static function booted(): void
    {
        $id = static::$collectionId;

        static::addGlobalScope('collection', function ($query) use ($id) {
            $query->where('collection_id', $id);
        });

        static::creating(function ($model) use ($id) {
            if (empty($model->collection_id)) {
                $model->collection_id = $id;
            }
        });
    }

    /**
     * Read the underlying payload as an array regardless of whether
     * the raw attribute was stored as a JSON string (fresh load from DB)
     * or as an already-decoded array (after a virtual setter merged
     * a new value into $this->attributes['payload']).
     *
     * Reading $this->payload directly hits the cast pipeline which calls
     * json_decode() — that crashes if the underlying attribute is already
     * an array, which happens whenever a virtual attribute setter on this
     * class chains with another setter in the same save cycle.
     */
    public function currentPayload(): array
    {
        $raw = $this->attributes['payload'] ?? null;
        if (is_array($raw)) return $raw;
        if ($raw === null || $raw === '') return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Pick a single string out of a `{en, hu, …}` translation map (or
     * pass through a plain scalar untouched). Static so callers like
     * Filament tables / the kanban / the calendar can normalise the
     * snapshot fields without owning a CollectionResource instance.
     */
    public static function pickLocale(mixed $value, ?string $lang = null): mixed
    {
        if (! is_array($value)) return $value;
        if ($value === []) return null;
        $isLocaleMap = true;
        foreach (array_keys($value) as $k) {
            if (! is_string($k) || strlen($k) > 5) { $isLocaleMap = false; break; }
        }
        if (! $isLocaleMap) return $value;
        $lang = $lang ?? app()->getLocale();
        return $value[$lang] ?? $value['en'] ?? $value['hu'] ?? array_values($value)[0] ?? null;
    }

    /**
     * Wrap a fully-built payload array in the `['payload' => …]` shape
     * Eloquent's Attribute::set callback expects, JSON-encoding it so
     * subsequent reads through the 'array' cast keep working when the
     * same save cycle chains multiple virtual setters on this model.
     *
     * @param  array<string, mixed>  $payload
     * @return array{payload: string}
     */
    public function packPayload(array $payload): array
    {
        return ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    /**
     * Read a payload key. If the value is a translation object
     * ({ en, hu, ... }), pick the requested locale (or app locale)
     * and fall back to en/hu/first available.
     *
     * Methods on this class **must not** return
     * Illuminate\Database\Eloquent\Casts\Attribute or Eloquent will
     * autodiscover them as virtual attribute accessors and call them
     * with zero arguments — see the bug that prompted this refactor.
     */
    public function readPayload(string $key, ?string $lang = null): mixed
    {
        $payload = $this->currentPayload();
        $value = $payload[$key] ?? null;
        if (! is_array($value)) {
            return $value;
        }
        // Looks like a translation map if all keys are short locale codes.
        if (array_all_keys_strings($value) && every_key_short($value)) {
            $lang = $lang ?? app()->getLocale();
            return $value[$lang] ?? $value['en'] ?? $value['hu'] ?? array_values($value)[0] ?? null;
        }
        return $value;
    }

    /**
     * Build the array shape Eloquent's Attribute::make set callback wants
     * for a payload-merge update. We return the JSON-encoded **string**
     * (not the raw array) so subsequent reads via the 'array' cast — which
     * blindly json_decode the underlying attribute — keep working when
     * multiple virtual setters chain in the same save cycle.
     */
    public function writePayload(string $key, mixed $value): array
    {
        $payload = $this->currentPayload();
        if ($value === null || $value === '') {
            unset($payload[$key]);
        } else {
            $payload[$key] = $value;
        }
        return ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    /**
     * Read one locale of a translation map under payload[$key].
     * Returns null when missing. Uses for Filament's per-locale fields.
     */
    public function readPayloadLocale(string $key, string $locale): ?string
    {
        $payload = $this->currentPayload();
        $value = $payload[$key] ?? null;
        if (is_array($value)) {
            return is_string($value[$locale] ?? null) ? $value[$locale] : null;
        }
        // Plain string is treated as the EN copy by default.
        return $locale === 'en' && is_string($value) ? $value : null;
    }

    /**
     * Set one locale of a translation map under payload[$key]. Returns
     * the array shape Attribute::make set wants.
     */
    public function writePayloadLocale(string $key, string $locale, mixed $value): array
    {
        $payload = $this->currentPayload();
        $current = $payload[$key] ?? null;

        if (is_string($current)) {
            // Promote an existing scalar string into the EN slot of a fresh map.
            $current = ['en' => $current];
        }
        if (! is_array($current)) {
            $current = [];
        }

        if ($value === null || $value === '') {
            unset($current[$locale]);
        } else {
            $current[$locale] = $value;
        }

        if (empty($current)) {
            unset($payload[$key]);
        } else {
            $payload[$key] = $current;
        }
        return ['payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }
}

if (! function_exists('array_all_keys_strings')) {
    function array_all_keys_strings(array $arr): bool
    {
        foreach (array_keys($arr) as $k) {
            if (! is_string($k)) return false;
        }
        return true;
    }
}

if (! function_exists('every_key_short')) {
    function every_key_short(array $arr, int $limit = 5): bool
    {
        foreach (array_keys($arr) as $k) {
            if (! is_string($k) || strlen($k) > $limit) return false;
        }
        return true;
    }
}
