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

    /** Lets KanbanBoard::reorder skip the saving guard during a drag (it
     *  validates the transition itself before calling save()). */
    public bool $skipStatusGuard = false;

    public function labelForLog(): string
    {
        return 'Task: ' . $this->title;
    }

    protected static function booted(): void
    {
        // Block illegal status transitions at the model level, so the
        // rule applies to every entry point (Filament form, kanban
        // drag, future API endpoints, tinker scripts).
        static::saving(function (Task $task) {
            if ($task->skipStatusGuard) return;
            if (! $task->isDirty('status')) return;
            $next = $task->status;
            // Run the gate against the *original* row state.
            $original = $task->getOriginal('status');
            if (! $original) return; // brand-new row — let it through.
            $tmp = (clone $task)->fill(['status' => $original]);
            $tmp->exists = true;
            $tmp->setRawAttributes($task->getOriginal());
            if (! $tmp->canTransitionTo(auth()->user(), $next)) {
                throw new \DomainException(__('admin.tasks.transition_denied'));
            }
        });
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

    public function proofs(): HasMany
    {
        return $this->hasMany(TaskProof::class)->orderByDesc('created_at');
    }

    /** Cheap "has the assignee delivered evidence yet?" check. */
    public function hasProof(): bool
    {
        return $this->proofs()->exists();
    }

    /**
     * Status transition gate. Returns true if `$user` is allowed to move
     * the task into `$nextStatus` from its current status. Admins are
     * unrestricted; otherwise:
     *
     * - Anyone with TASKS_EDIT can move to TODO / IN_PROGRESS.
     * - Assignees move to TESTING (only). Once a proof is attached.
     * - Only the supervisor (or admin) can flip TESTING → DONE.
     * - DONE always requires at least one proof.
     */
    public function canTransitionTo(?User $user, string $nextStatus): bool
    {
        if (! $user) return false;
        if (! in_array($nextStatus, self::statuses(), true)) return false;
        if ($user->isAdmin()) {
            return $nextStatus !== self::STATUS_DONE || $this->hasProof();
        }
        if (! $user->can(\App\Auth\Perm::TASKS_EDIT)) return false;

        $isAssignee = $this->assignees()->where('users.id', $user->id)->exists();
        $isSupervisor = $this->supervisor_id === $user->id;

        // Backwards transitions and TODO / IN_PROGRESS shifts are open
        // to any assignee or the supervisor.
        if (in_array($nextStatus, [self::STATUS_TODO, self::STATUS_IN_PROGRESS], true)) {
            return $isAssignee || $isSupervisor;
        }
        if ($nextStatus === self::STATUS_TESTING) {
            // "I'm done — please review." Requires evidence + assignee role.
            if (! ($isAssignee || $isSupervisor)) return false;
            return $this->hasProof();
        }
        if ($nextStatus === self::STATUS_DONE) {
            // Only the supervisor signs off, and the proof must exist.
            return $isSupervisor && $this->hasProof();
        }
        return false;
    }

    /** @return array<int, string> Valid next statuses for $user from current. */
    public function allowedTransitionsFor(?User $user): array
    {
        return array_values(array_filter(
            self::statuses(),
            fn ($s) => $s === $this->status || $this->canTransitionTo($user, $s),
        ));
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
