<?php

namespace App\Concerns;

use App\Models\ActivityLog;

/**
 * Drop-in trait that logs created/updated/deleted lifecycle events to
 * the activity_logs table. The author is read from auth(); attribute
 * diffs are captured on update only (so we don't dump the entire
 * payload column on save). Subjects are summarised via labelForLog()
 * which models can override.
 */
trait LogsActivity
{
    /**
     * Per-instance opt-out — set inside withoutActivityLog() so the
     * lifecycle listeners skip writing for this save. Used by callers
     * who want to emit a more meaningful custom event in the same
     * transaction (e.g. "accepted" rather than "updated").
     */
    public bool $skipActivityLog = false;

    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            if ($model->skipActivityLog ?? false) return;
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            if ($model->skipActivityLog ?? false) return;
            $original = $model->getOriginal();
            $diff = [];
            foreach ($model->getChanges() as $key => $value) {
                if (in_array($key, ['updated_at', 'remember_token', 'password'], true)) continue;
                $diff[$key] = [
                    'from' => $original[$key] ?? null,
                    'to' => $value,
                ];
            }
            if (empty($diff)) return;
            $model->logActivity('updated', $diff);
        });

        static::deleted(function ($model) {
            if ($model->skipActivityLog ?? false) return;
            $model->logActivity('deleted');
        });
    }

    /**
     * Write an arbitrary entry against this model. Callers use this to
     * record domain-specific events (accepted, rejected, …) that are
     * more useful than the generic "updated" payload diff.
     *
     * @param  array<string, mixed>|null  $changes
     */
    public function logActivity(string $event, ?array $changes = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => static::class,
            'subject_id' => (string) $this->getKey(),
            'subject_label' => method_exists($this, 'labelForLog') ? $this->labelForLog() : (string) $this->getKey(),
            'changes' => $changes,
        ]);
    }

    /**
     * Run $callback with auto-logging suppressed on this instance, so
     * the caller can emit a single, hand-shaped log entry instead of
     * the generic created/updated one.
     *
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public function withoutActivityLog(callable $callback)
    {
        $previous = $this->skipActivityLog;
        $this->skipActivityLog = true;
        try {
            return $callback();
        } finally {
            $this->skipActivityLog = $previous;
        }
    }
}
