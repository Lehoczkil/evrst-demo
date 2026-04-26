<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ObjectFile extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'resource_id', 'key', 'disk', 'path', 'mime_type', 'size'];

    public $incrementing = false;

    protected $keyType = 'string';

    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
