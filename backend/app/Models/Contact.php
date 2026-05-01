<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'contact_group_id',
        'name',
        'phone',
        'email',
        'notes',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ContactGroup::class, 'contact_group_id');
    }

    public function labelForLog(): string
    {
        return 'Contact: ' . ($this->name ?? "#{$this->id}");
    }
}
