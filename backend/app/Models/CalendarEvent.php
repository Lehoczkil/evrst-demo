<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use App\Models\Cms\AboutProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-only calendar entries — separate from {@see \App\Models\Cms\Event}
 * which feeds the public site through the API. CalendarEvents never leak
 * out of the panel.
 */
class CalendarEvent extends Model
{
    use LogsActivity;

    public function labelForLog(): string
    {
        return 'Calendar event: ' . $this->title;
    }

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'description',
        'start_at',
        'end_at',
        'all_day',
        'color',
        'location',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(AboutProject::class, 'project_id', 'id');
    }
}
