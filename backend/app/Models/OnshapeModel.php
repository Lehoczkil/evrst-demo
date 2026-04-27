<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OnshapeModel extends Model
{
    use LogsActivity;

    public const GLB_IDLE    = 'idle';
    public const GLB_QUEUED  = 'queued';
    public const GLB_RUNNING = 'running';
    public const GLB_FAILED  = 'failed';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'document_id',
        'workspace_id',
        'element_id',
        'share_url',
        'thumbnail_path',
        'glb_disk',
        'glb_path',
        'glb_size',
        'glb_exported_at',
        'glb_status',
        'glb_error',
    ];

    protected $casts = [
        'glb_size' => 'integer',
        'glb_exported_at' => 'datetime',
    ];

    public function labelForLog(): string
    {
        return 'Onshape model: ' . $this->title;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build the canonical Onshape embed URL from the parsed IDs. Falls
     * back to the raw share_url when the IDs are missing — Onshape's
     * `/url/` redirect handles share URLs that include a token.
     */
    protected function embedUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->document_id && $this->workspace_id) {
                    $base = "https://cad.onshape.com/documents/{$this->document_id}/w/{$this->workspace_id}";
                    if ($this->element_id) {
                        $base .= "/e/{$this->element_id}";
                    }
                    return $base . '?embed=true&allowAuth=true';
                }
                return $this->share_url;
            },
        );
    }

    /**
     * Pull `did`, `wid`, and `eid` out of a pasted Onshape URL. Returns
     * an associative array with keys `document_id`, `workspace_id`,
     * `element_id`, or null entries when they aren't present.
     *
     * @return array{document_id: ?string, workspace_id: ?string, element_id: ?string}
     */
    /** Public URL of the cached GLB on the public disk, or null if none. */
    protected function glbUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->glb_path) return null;
                try {
                    return Storage::disk($this->glb_disk ?: 'public')->url($this->glb_path);
                } catch (\Throwable) {
                    return null;
                }
            },
        );
    }

    public function hasGlb(): bool
    {
        return (bool) $this->glb_path;
    }

    public function deleteGlbFile(): void
    {
        if (! $this->glb_path) return;
        try {
            Storage::disk($this->glb_disk ?: 'public')->delete($this->glb_path);
        } catch (\Throwable) {
            // best-effort
        }
    }

    public static function parseShareUrl(?string $url): array
    {
        $out = ['document_id' => null, 'workspace_id' => null, 'element_id' => null];
        if (! $url) return $out;

        // Examples:
        //   https://cad.onshape.com/documents/{did}/w/{wid}/e/{eid}
        //   https://cad.onshape.com/documents/{did}/v/{vid}/e/{eid}
        //   https://cad.onshape.com/documents/{did}
        if (preg_match('#/documents/([a-z0-9]{16,32})#i', $url, $m)) {
            $out['document_id'] = $m[1];
        }
        if (preg_match('#/(?:w|v|m)/([a-z0-9]{16,32})#i', $url, $m)) {
            $out['workspace_id'] = $m[1];
        }
        if (preg_match('#/e/([a-z0-9]{16,32})#i', $url, $m)) {
            $out['element_id'] = $m[1];
        }
        return $out;
    }
}
