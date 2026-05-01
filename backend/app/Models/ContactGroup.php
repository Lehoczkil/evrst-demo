<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactGroup extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->orderBy('position');
    }

    public function labelForLog(): string
    {
        return 'Contact group: ' . ($this->name ?? "#{$this->id}");
    }
}
