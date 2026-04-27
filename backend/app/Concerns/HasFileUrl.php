<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

/**
 * Drop-in trait for models that store a single file on a Laravel disk
 * and expose its public URL. Standardises three things across
 * Drawing / TaskProof / OnshapeModel (and any future child model):
 *
 *   - `fileUrl` accessor returns the resolved public URL or null
 *   - `deleteFile()` is a best-effort cleanup helper (used by the
 *      DeleteAction `before` hook on the Filament tables)
 *   - subclasses can override `fileDiskAttribute()` /
 *      `filePathAttribute()` if they store their disk + path under
 *      different column names (OnshapeModel uses `glb_disk` /
 *      `glb_path`, the others use plain `disk` / `path`)
 */
trait HasFileUrl
{
    public function fileDiskAttribute(): string
    {
        return 'disk';
    }

    public function filePathAttribute(): string
    {
        return 'path';
    }

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $path = $this->{$this->filePathAttribute()};
                if (! $path) return null;
                $disk = $this->{$this->fileDiskAttribute()} ?: 'public';
                try {
                    return Storage::disk($disk)->url($path);
                } catch (\Throwable) {
                    return null;
                }
            },
        );
    }

    public function deleteFile(): void
    {
        $path = $this->{$this->filePathAttribute()};
        if (! $path) return;
        $disk = $this->{$this->fileDiskAttribute()} ?: 'public';
        try {
            Storage::disk($disk)->delete($path);
        } catch (\Throwable) {
            // best-effort; row deletion proceeds anyway.
        }
    }
}
