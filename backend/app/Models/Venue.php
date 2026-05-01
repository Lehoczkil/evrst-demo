<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    public const KEY_OFFICE  = 'office';
    public const KEY_PRIVATE = 'private';

    protected $fillable = ['key', 'name'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function isPrivate(): bool
    {
        return $this->key === self::KEY_PRIVATE;
    }
}
