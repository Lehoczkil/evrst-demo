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
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'event' => 'created',
                'subject_type' => $model::class,
                'subject_id' => (string) $model->getKey(),
                'subject_label' => method_exists($model, 'labelForLog') ? $model->labelForLog() : (string) $model->getKey(),
                'changes' => null,
            ]);
        });

        static::updated(function ($model) {
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

            ActivityLog::create([
                'user_id' => auth()->id(),
                'event' => 'updated',
                'subject_type' => $model::class,
                'subject_id' => (string) $model->getKey(),
                'subject_label' => method_exists($model, 'labelForLog') ? $model->labelForLog() : (string) $model->getKey(),
                'changes' => $diff,
            ]);
        });

        static::deleted(function ($model) {
            ActivityLog::create([
                'user_id' => auth()->id(),
                'event' => 'deleted',
                'subject_type' => $model::class,
                'subject_id' => (string) $model->getKey(),
                'subject_label' => method_exists($model, 'labelForLog') ? $model->labelForLog() : (string) $model->getKey(),
                'changes' => null,
            ]);
        });
    }
}
