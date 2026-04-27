<?php

namespace App\Models;

use App\Concerns\HasFileUrl;
use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskProof extends Model
{
    use HasFileUrl, LogsActivity;

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

    /** Tell HasFileUrl which columns store the disk + path. */
    public function fileDiskAttribute(): string { return 'file_disk'; }
    public function filePathAttribute(): string { return 'file_path'; }

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
}
