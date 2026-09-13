<?php

namespace App\Models;

use App\Concerns\HasFileUrl;
use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Bug report submitted from inside the admin panel.
 *
 * Anyone with a panel session can file one (Members included). Status
 * lifecycle is owned by the bug-management page; updates require the
 * `bugs.triage` permission.
 */
class BugReport extends Model
{
    use HasFileUrl, LogsActivity;

    public const STATUS_OPEN        = 'open';
    public const STATUS_TRIAGING    = 'triaging';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED    = 'resolved';
    public const STATUS_CLOSED      = 'closed';
    public const STATUS_WONT_FIX    = 'wont_fix';

    public const SEVERITY_LOW      = 'low';
    public const SEVERITY_MEDIUM   = 'medium';
    public const SEVERITY_HIGH     = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [
            self::STATUS_OPEN, self::STATUS_TRIAGING, self::STATUS_IN_PROGRESS,
            self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_WONT_FIX,
        ];
    }

    /** @return array<int, string> */
    public static function severities(): array
    {
        return [
            self::SEVERITY_LOW, self::SEVERITY_MEDIUM,
            self::SEVERITY_HIGH, self::SEVERITY_CRITICAL,
        ];
    }

    /** @return array<int, string> */
    public static function openStatuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_TRIAGING, self::STATUS_IN_PROGRESS];
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::openStatuses(), true);
    }

    /**
     * A report holds a LIST of screenshots, so the single-path half of
     * HasFileUrl does not apply — `fileUrl()` and the trait's own
     * `deleteFile()` both read one column. `deleteFile()` is overridden
     * below to sweep the whole list; the trait's `deleting` hook still
     * calls it, so a deleted report still takes its images with it.
     */
    public function fileDiskAttribute(): string
    {
        return 'disk';
    }

    /** Always the public disk; there is no per-row disk column. */
    public function getDiskAttribute(): string
    {
        return 'public';
    }

    protected $fillable = [
        'reporter_id', 'assignee_id',
        'title', 'description',
        'status', 'severity',
        'page_url', 'environment',
        'screenshots', 'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'environment' => 'array',
        'screenshots' => 'array',
        'resolved_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
        'severity' => self::SEVERITY_MEDIUM,
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function labelForLog(): string
    {
        return 'Bug: ' . $this->title;
    }

    /**
     * Convenience accessor — flips `resolved_at` whenever the status
     * crosses into / out of a closed state. Keeps the column consistent
     * even when triage manually toggles status from the form.
     */
    protected static function booted(): void
    {
        static::saving(function (self $bug) {
            $closed = in_array($bug->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED, self::STATUS_WONT_FIX], true);
            if ($closed && ! $bug->resolved_at) {
                $bug->resolved_at = now();
            }
            if (! $closed && $bug->resolved_at) {
                $bug->resolved_at = null;
            }
        });
    }

    /**
     * Public URLs for every attached screenshot, in the order the
     * reporter arranged them.
     *
     * @return list<string>
     */
    public function screenshotUrls(): array
    {
        $disk = Storage::disk($this->disk);

        return collect($this->screenshots ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(function (string $path) use ($disk) {
                try {
                    return $disk->url($path);
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Every image, not just the first. The trait's `deleting` hook calls
     * this, so deleting a report — by row action, bulk action or shell —
     * still clears the volume.
     */
    public function deleteFile(): void
    {
        $paths = collect($this->screenshots ?? [])
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values()
            ->all();

        if ($paths === []) {
            return;
        }

        try {
            Storage::disk($this->disk)->delete($paths);
        } catch (\Throwable) {
            // Best effort; the row still goes.
        }
    }
}
