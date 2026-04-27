<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TaskProof extends Model
{
    use LogsActivity;

    public const KIND_IMAGE = 'image';
    public const KIND_FILE  = 'file';   // 3D model (GLB/STL/STEP), PDF, …
    public const KIND_LINK  = 'link';
    public const KIND_NOTE  = 'note';

    /** @return array<int, string> */
    public static function kinds(): array
    {
        return [self::KIND_IMAGE, self::KIND_FILE, self::KIND_LINK, self::KIND_NOTE];
    }

    protected $fillable = [
        'task_id',
        'user_id',
        'kind',
        'title',
        'body',
        'link_url',
        'file_disk',
        'file_path',
        'file_mime',
        'file_size',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function labelForLog(): string
    {
        return 'Proof: ' . $this->title;
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Resolve the public URL for image / file proofs. Null for note / link.
     */
    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->file_path) return null;
                try {
                    return Storage::disk($this->file_disk ?: 'public')->url($this->file_path);
                } catch (\Throwable) {
                    return null;
                }
            },
        );
    }

    public function deleteFile(): void
    {
        if (! $this->file_path) return;
        try {
            Storage::disk($this->file_disk ?: 'public')->delete($this->file_path);
        } catch (\Throwable) {
            // best-effort; row deletion proceeds anyway.
        }
    }
}
