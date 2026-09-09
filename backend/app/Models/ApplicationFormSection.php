<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One numbered card on the public join-us form.
 */
class ApplicationFormSection extends Model
{
    use LogsActivity;

    protected $fillable = [
        'key',
        'title',
        'description',
        'position',
        'is_active',
    ];

    protected $casts = [
        'title' => 'array',
        'description' => 'array',
        'position' => 'integer',
        'is_active' => 'boolean',
    ];

    public function labelForLog(): string
    {
        return 'Application form section: ' . (TeamMemberGroup::pickLocale($this->title) ?? $this->key);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ApplicationFormField::class, 'section_id')->orderBy('position');
    }

    public function activeFields(): HasMany
    {
        return $this->fields()->where('is_active', true);
    }
}
