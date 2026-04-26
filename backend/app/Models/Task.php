<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use LogsActivity;

    public function labelForLog(): string
    {
        return 'Task: ' . $this->title;
    }

    public const STATUS_TODO = 'TODO';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_TESTING = 'TESTING';
    public const STATUS_DONE = 'DONE';

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_TESTING, self::STATUS_DONE];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_TODO => 'Todo',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_TESTING => 'Testing',
            self::STATUS_DONE => 'Done',
        ];
    }

    public static function statusLabel(string $key): string
    {
        return self::statusLabels()[$key] ?? $key;
    }

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
        'supervisor_id',
        'created_by',
        'position',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_user')
            ->withPivot('assigned_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Returns all users who should be notified about activity on this task
     * (assignees + supervisor), de-duplicated and optionally excluding a
     * given user (so a comment author isn't pinged about their own comment).
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function watchers(?int $excludeUserId = null): \Illuminate\Support\Collection
    {
        $this->loadMissing(['assignees', 'supervisor']);
        $users = $this->assignees->all();
        if ($this->supervisor) {
            $users[] = $this->supervisor;
        }
        $unique = collect($users)->unique('id');
        if ($excludeUserId !== null) {
            $unique = $unique->reject(fn ($u) => $u->id === $excludeUserId);
        }
        return $unique->values();
    }
}
