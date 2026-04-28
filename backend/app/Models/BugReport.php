<?php

namespace App\Models;

use App\Concerns\HasFileUrl;
use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * HasFileUrl override — this model stores the screenshot path on
     * `screenshot_path` and has no per-row disk column (we always use
     * the public disk). The trait calls `fileDiskAttribute()` /
     * `filePathAttribute()` to resolve the URL, so override both.
     */
    public function filePathAttribute(): string
    {
        return 'screenshot_path';
    }

    public function fileDiskAttribute(): string
    {
        return 'disk';
    }

    /**
     * Always serve screenshots from the public disk; we don't track a
     * per-row disk column.
     */
    public function getDiskAttribute(): string
    {
        return 'public';
    }

    protected $fillable = [
        'reporter_id', 'assignee_id',
        'title', 'description',
        'status', 'severity',
        'page_url', 'environment',
        'screenshot_path', 'admin_notes',
        'resolved_at',
    ];

    protected $casts = [
        'environment' => 'array',
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

    public function screenshotUrl(): Attribute
    {
        return Attribute::get(fn () => $this->fileUrl());
    }
}
