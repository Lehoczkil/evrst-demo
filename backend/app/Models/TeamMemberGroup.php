<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeamMemberGroup extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'parent_id',
        'slug',
        'name',
        'description',
        'kind',
        'position',
        'is_public',
    ];

    protected $casts = [
        'name' => 'array',
        'description' => 'array',
        'is_public' => 'boolean',
        'position' => 'integer',
    ];

    public function labelForLog(): string
    {
        $name = is_array($this->name) ? ($this->name['en'] ?? array_values($this->name)[0] ?? null) : $this->name;
        return 'Team member group: ' . ($name ?? "#{$this->id}");
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(TeamMember::class, 'team_member_team_member_group')
            ->using(TeamMemberAssignment::class)
            ->withPivot(['id', 'is_primary', 'title', 'started_at', 'ended_at', 'position'])
            ->withTimestamps();
    }

    public function currentMembers(): BelongsToMany
    {
        return $this->members()->wherePivotNull('ended_at');
    }

    /**
     * Convenience accessor: pick the localized name in the current
     * locale, falling back to en → hu → first available.
     */
    protected function localizedName(): Attribute
    {
        return Attribute::make(
            get: fn () => self::pickLocale($this->name),
        );
    }

    public static function pickLocale(mixed $value, ?string $lang = null): mixed
    {
        if (! is_array($value) || $value === []) return $value;
        $lang = $lang ?? app()->getLocale();
        return $value[$lang] ?? $value['en'] ?? $value['hu'] ?? array_values($value)[0] ?? null;
    }
}
