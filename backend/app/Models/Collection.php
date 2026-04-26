<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Collection extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id', 'name', 'slug', 'description'];

    public $incrementing = false;

    protected $keyType = 'string';

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
