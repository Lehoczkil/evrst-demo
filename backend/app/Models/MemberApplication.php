<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberApplication extends Model
{
    use HasUuids, LogsActivity;

    public function labelForLog(): string
    {
        return 'Application: ' . $this->name;
    }

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'email',
        'name',
        'university',
        'education',
        'faculty',
        'why',
        'hours',
        'languages',
        'department',
        'tasks',
        'skills',
        'status',
        'reviewed_at',
        'reviewed_by',
        'team_member_id',
    ];

    protected $casts = [
        'languages' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
