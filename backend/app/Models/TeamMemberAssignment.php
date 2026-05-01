<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TeamMemberAssignment extends Pivot
{
    protected $table = 'team_member_team_member_group';

    public $incrementing = true;

    public $timestamps = true;

    protected $casts = [
        'is_primary' => 'boolean',
        'title' => 'array',
        'started_at' => 'date',
        'ended_at' => 'date',
        'position' => 'integer',
    ];
}
