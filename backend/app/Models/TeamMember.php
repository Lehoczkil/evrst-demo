<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class TeamMember extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'email_private',
        'discord_nick',
        'discord_username',
        'discord_id',
        'degree',
        'bio',
        'photo_path',
        'joined_at',
        'left_at',
        'is_public',
        'position',
        'meta',
    ];

    protected $casts = [
        'degree' => 'array',
        'bio' => 'array',
        'meta' => 'array',
        'joined_at' => 'date',
        'left_at' => 'date',
        'is_public' => 'boolean',
        'position' => 'integer',
    ];

    public function labelForLog(): string
    {
        return 'Team member: ' . ($this->name ?? "#{$this->id}");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(TeamMemberGroup::class, 'team_member_team_member_group')
            ->using(TeamMemberAssignment::class)
            ->withPivot(['id', 'is_primary', 'title', 'started_at', 'ended_at', 'position'])
            ->withTimestamps();
    }

    public function currentGroups(): BelongsToMany
    {
        return $this->groups()->wherePivotNull('ended_at');
    }

    public function primaryGroup(): BelongsToMany
    {
        return $this->groups()->wherePivot('is_primary', true);
    }

    /**
     * Replace this member's group assignments and mark one of them primary.
     *
     * `started_at` is stamped on *newly* attached rows only. The pivot has
     * its own id so the same position can be held across several spans, and
     * a row with no start date can't take part in that timeline — but
     * re-stamping every row on each save would rewrite history every time
     * someone edits an unrelated field.
     *
     * @param  array<int, int|string>  $groupIds
     */
    public function syncGroupAssignments(array $groupIds, ?int $primaryGroupId): void
    {
        $groupIds = array_values(array_unique(array_map('intval', array_filter(
            $groupIds,
            static fn ($id) => $id !== null && $id !== '',
        ))));

        $existing = $this->groups()->pluck('team_member_groups.id')->all();

        $pivot = [];
        foreach ($groupIds as $id) {
            $pivot[$id] = in_array($id, $existing, true)
                ? []   // leave the existing row's started_at alone
                : ['is_primary' => false, 'started_at' => now()];
        }

        $this->groups()->sync($pivot);
        $this->setPrimaryGroup($primaryGroupId);
    }

    /**
     * Set exactly one primary assignment, clearing any others. Pass null
     * to clear without setting a new one.
     */
    public function setPrimaryGroup(?int $groupId): void
    {
        DB::transaction(function () use ($groupId) {
            $this->groups()->newPivotStatement()
                ->where('team_member_id', $this->id)
                ->update(['is_primary' => false]);

            if ($groupId !== null) {
                $this->groups()->newPivotStatement()
                    ->where('team_member_id', $this->id)
                    ->where('team_member_group_id', $groupId)
                    ->update(['is_primary' => true]);
            }
        });
    }
}
