<?php

namespace App\Models;

use App\Concerns\HasFileUrl;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Drawing extends Model
{
    use HasFileUrl;

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

    /** Backwards-compatible alias — every Filament + Blade reference
     *  uses $drawing->url. Keep both names working so we don't have to
     *  rewrite the gallery + preview Blades in this pass. */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->file_url,
        );
    }
}
