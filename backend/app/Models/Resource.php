<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resource extends Model
{
    use HasFactory, HasUuids, LogsActivity;

    public function labelForLog(): string
    {
        return $this->resourceLabel();
    }

    protected $fillable = ['id', 'collection_id', 'payload', 'position'];

    protected $casts = [
        'payload' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function objects(): HasMany
    {
        return $this->hasMany(ObjectFile::class);
    }

    /**
     * Convenience label used by the raw resources/collections admin
     * inspector. Renamed from `getTitleAttribute` so it doesn't shadow
     * the modern Attribute-based `title()` accessors on subclasses
     * (Event, Mentor, AboutGoal, AboutProject, Sponsor, …).
     */
    public function resourceLabel(): string
    {
        $payload = $this->payload ?? [];
        $name = $payload['name'] ?? null;
        $title = $payload['title'] ?? null;

        // Translation maps come back as ['en'=>'…','hu'=>'…'] — pick one.
        if (is_array($name)) {
            $name = $name[app()->getLocale()] ?? $name['en'] ?? $name['hu'] ?? null;
        }
        if (is_array($title)) {
            $title = $title[app()->getLocale()] ?? $title['en'] ?? $title['hu'] ?? null;
        }

        return (string) ($name ?? $title ?? $this->id);
    }
}
