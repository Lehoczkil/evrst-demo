<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inventory row: one Item placed at one Venue, optionally with a
 * TeamMember owner (only when venue is private). The same Item can
 * have many stocks — e.g. 5 in the office, 1 with Alice, 1 with Bob.
 */
class ItemStock extends Model
{
    use LogsActivity;

    protected $fillable = [
        'item_id',
        'venue_id',
        'owner_team_member_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function ownerTeamMember(): BelongsTo
    {
        return $this->belongsTo(TeamMember::class, 'owner_team_member_id');
    }

    public function labelForLog(): string
    {
        $name = $this->item?->name ?? 'item';
        $venue = $this->venue?->name ?? 'venue';
        return "Stock: {$name} @ {$venue}";
    }

    /**
     * Office stock has no owner — keep the two columns from disagreeing
     * regardless of which write path produced the row.
     */
    protected static function booted(): void
    {
        static::saving(function (self $stock) {
            $venue = $stock->venue_id ? Venue::find($stock->venue_id) : null;
            if ($venue && ! $venue->isPrivate()) {
                $stock->owner_team_member_id = null;
            }
        });
    }
}
