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
     *  validates the transition itself before calling save()), and lets
     *  the auto-promote routine bypass the gate when system-driven. */
    public bool $skipStatusGuard = false;

    public function labelForLog(): string
    {
        return 'Task: ' . $this->title;
    }

    protected static function booted(): void
    {
        // Block illegal status transitions at the model level so the
        // rule applies to every entry point (Filament form, kanban
        // drag, future API endpoints, tinker scripts).
        static::saving(function (Task $task) {
            if ($task->skipStatusGuard) return;
            if (! $task->isDirty('status')) return;
            $next = $task->status;
            $original = $task->getOriginal('status');
            if (! $original) return; // brand-new row — let it through.
            $tmp = (clone $task)->fill(['status' => $original]);
            $tmp->exists = true;
            $tmp->setRawAttributes($task->getOriginal());
            if (! $tmp->canTransitionTo(auth()->user(), $next)) {
                throw new \DomainException(__('admin.tasks.transition_denied'));
            }
        });

        // Auto-promote the parent once a child's status changes:
        //  - all children DONE     → parent DONE
        //  - all children >= TESTING (no TODO / IN_PROGRESS) → parent TESTING
        // Both cases skip the canTransitionTo gate (system-driven) but
        // still write a "promoted" activity log entry so the audit
        // trail records who triggered the cascade.
        static::saved(function (Task $task) {
            if (! $task->wasChanged('status')) return;
            if (! $task->parent_task_id) return;
            $parent = Task::find($task->parent_task_id);
            if (! $parent) return;
            $parent->autoPromoteFromChildren();
        });
    }

    public const STATUS_TODO = 'TODO';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_TESTING = 'TESTING';
    public const STATUS_DONE = 'DONE';

    public const PRIORITY_URGENT = 'urgent';
    public const PRIORITY_HIGH   = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW    = 'low';

    public const CATEGORY_DOCS    = 'docs';
    public const CATEGORY_WEBPAGE = 'webpage';
    public const CATEGORY_MODEL   = 'model_3d';
    public const CATEGORY_ADMIN   = 'administration';
    public const CATEGORY_HARDWARE = 'hardware';
    public const CATEGORY_SOFTWARE = 'software';
    public const CATEGORY_OUTREACH = 'outreach';
    public const CATEGORY_OTHER    = 'other';

    /** @return array<int, string> */
    public static function statuses(): array
    {
        return [self::STATUS_TODO, self::STATUS_IN_PROGRESS, self::STATUS_TESTING, self::STATUS_DONE];
    }

    /** @return array<int, string> */
    public static function priorities(): array
    {
        return [self::PRIORITY_URGENT, self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW];
    }

    /** @return array<int, string> */
    public static function categories(): array
    {
        return [
            self::CATEGORY_DOCS, self::CATEGORY_WEBPAGE, self::CATEGORY_MODEL,
            self::CATEGORY_ADMIN, self::CATEGORY_HARDWARE, self::CATEGORY_SOFTWARE,
            self::CATEGORY_OUTREACH, self::CATEGORY_OTHER,
        ];
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
        'parent_task_id',
        'category',
        'priority',
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

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id')->orderBy('position')->orderBy('id');
    }

    /** Cheap "has the assignee delivered evidence yet?" check. */
    public function hasProof(): bool
    {
        return $this->proofs()->exists();
    }

    /** True if the row has at least one child task in any state. */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Status transition gate. Returns true if `$user` is allowed to move
     * the task into `$nextStatus` from its current status.
     *
     *  - Anyone with TASKS_EDIT can move to TODO / IN_PROGRESS.
     *  - Assignees + supervisors can move to TESTING (with proof).
     *  - Only the supervisor (or admin) can flip to DONE (with proof).
     *  - A parent task with children cannot reach TESTING until every
     *    child is at least TESTING, and cannot reach DONE until every
     *    child is DONE.
     */
    public function canTransitionTo(?User $user, string $nextStatus): bool
    {
        if (! $user) return false;
        if (! in_array($nextStatus, self::statuses(), true)) return false;

        // Parent / children gate — applies to every role including admin.
        if ($nextStatus === self::STATUS_DONE && ! $this->allChildrenDone()) {
            return false;
        }
        if ($nextStatus === self::STATUS_TESTING && ! $this->allChildrenAtLeastTesting()) {
            return false;
        }

        if ($user->isAdmin()) {
            return $nextStatus !== self::STATUS_DONE || $this->hasProof();
        }
        if (! $user->can(\App\Auth\Perm::TASKS_EDIT)) return false;

        $isAssignee = $this->assignees()->where('users.id', $user->id)->exists();
        $isSupervisor = $this->supervisor_id === $user->id;

        if (in_array($nextStatus, [self::STATUS_TODO, self::STATUS_IN_PROGRESS], true)) {
            return $isAssignee || $isSupervisor;
        }
        if ($nextStatus === self::STATUS_TESTING) {
            if (! ($isAssignee || $isSupervisor)) return false;
            return $this->hasProof();
        }
        if ($nextStatus === self::STATUS_DONE) {
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
     * True when every child is DONE. Vacuously true when there are no
     * children — childless tasks behave exactly as before.
     */
    public function allChildrenDone(): bool
    {
        if (! $this->parent_task_id && ! $this->relationLoaded('children')) {
            // Cheap exit for leaf rows.
            if (! $this->hasChildren()) return true;
        }
        return $this->children()->where('status', '!=', self::STATUS_DONE)->doesntExist();
    }

    /**
     * True when every child is at least TESTING (i.e. no TODO or
     * IN_PROGRESS children). Vacuously true with no children.
     */
    public function allChildrenAtLeastTesting(): bool
    {
        return $this->children()
            ->whereIn('status', [self::STATUS_TODO, self::STATUS_IN_PROGRESS])
            ->doesntExist();
    }

    /**
     * Promote `$this` parent based on its children's combined state.
     * Called on every child save. Skipping the canTransitionTo gate is
     * intentional — the cascade is system-driven and we still log it
     * to activity_logs as a `promoted` event.
     */
    public function autoPromoteFromChildren(): void
    {
        if (! $this->hasChildren()) return;

        $original = $this->status;
        $next = $original;

        if ($this->allChildrenDone()) {
            $next = self::STATUS_DONE;
        } elseif ($this->allChildrenAtLeastTesting()) {
            // Don't drop a parent that's already DONE back to TESTING.
            if ($original !== self::STATUS_DONE) {
                $next = self::STATUS_TESTING;
            }
        }

        if ($next === $original) return;

        $this->status = $next;
        $this->skipStatusGuard = true;
        $this->withoutActivityLog(fn () => $this->save());
        $this->skipStatusGuard = false;
        $this->logActivity('promoted', [
            'status' => ['from' => $original, 'to' => $next],
            'reason' => 'all children reached ' . $next,
        ]);
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
