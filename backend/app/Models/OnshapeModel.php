<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnshapeModel extends Model
{
    use LogsActivity;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'document_id',
        'workspace_id',
        'element_id',
        'share_url',
        'thumbnail_path',
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
