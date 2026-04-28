<?php

namespace App\Models;

use App\Auth\Perm;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'password_changed_at',
        'avatar',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Look up the TeamMember row that points at this user via
     * payload.user.id. There's no FK in the schema (TeamMember is a
     * JSON-payload Resource) so we filter the resources table directly.
     * Returns null when the user hasn't been linked to a member yet.
     */
    public function teamMember(): ?\App\Models\Cms\TeamMember
    {
        return \App\Models\Cms\TeamMember::query()
            ->whereRaw("json_extract(payload, '$.user.id') = ?", [$this->id])
            ->first();
    }

    /**
     * Permission check. When called with a key shaped like our Perm::*
     * constants (`<resource>.<action>`) we look it up against the user's
     * role. Anything else is delegated to the framework so Laravel Gates
     * + Filament's own checks keep working.
     */
    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && str_contains($abilities, '.')) {
            return $this->hasPermission($abilities);
        }
        return parent::can($abilities, $arguments);
    }

    public function hasPermission(string $key): bool
    {
        $this->loadMissing('role.permissions');
        return $this->role?->hasPermission($key) ?? false;
    }

    public function isAdmin(): bool
    {
        $this->loadMissing('role');
        return $this->role?->key === Perm::ROLE_ADMIN;
    }

    /**
     * Required by Filament's panel access check. Anyone with an active
     * role on the admin panel may sign in — Member is read-only at the
     * resource level, but they still need a session.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role_id !== null;
    }

    /**
     * Show the uploaded avatar in the topbar / EditProfile when set.
     * Falls back to Filament's auto-generated initials avatar otherwise.
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar) return null;
        if (preg_match('#^https?://#i', $this->avatar)) return $this->avatar;
        try {
            return Storage::disk('public')->url(ltrim($this->avatar, '/'));
        } catch (\Throwable) {
            return null;
        }
    }
}
