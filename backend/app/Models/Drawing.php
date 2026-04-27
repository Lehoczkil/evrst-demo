<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Drawing extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'disk',
        'path',
        'mime',
        'size',
        'width',
        'height',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->path ? Storage::disk($this->disk ?: 'public')->url($this->path) : null,
        );
    }

    public function deleteFile(): void
    {
        if (! $this->path) return;
        try {
            Storage::disk($this->disk ?: 'public')->delete($this->path);
        } catch (\Throwable) {
            // best-effort; row deletion proceeds either way
        }
    }
}
