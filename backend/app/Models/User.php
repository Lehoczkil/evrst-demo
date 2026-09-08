<?php

namespace App\Models;

use App\Auth\Perm;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasLocalePreference
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

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    /**
     * Where mail for this user actually goes.
     *
     * `email` is the login — the org address (laszlo.lehoczki@evrst.hu) for
     * every provisioned team member. Delivery is a separate question,
     * because an org address only receives once a real mailbox exists at
     * the mail host. `mail.deliver_to_org_addresses` picks the order:
     * off (default) prefers the member's personal inbox so temp passwords
     * and reset links are never sent into a void; on prefers the org
     * address. Either way the other address is the fallback, so a member
     * missing one of the two still gets their mail.
     */
    /**
     * Render notifications in the language the member picked in the panel.
     * Returning null (nobody has touched the switcher yet) lets Laravel fall
     * through to the app locale, which is also what the queue worker boots
     * with — set APP_LOCALE=hu to make that the Hungarian default.
     */
    public function preferredLocale(): ?string
    {
        return $this->locale;
    }

    public function deliveryEmail(): ?string
    {
        $this->loadMissing('teamMember');

        $private = $this->teamMember?->email_private;
        $account = $this->email ?: null;

        $preferred = config('mail.deliver_to_org_addresses')
            ? [$account, $private]
            : [$private, $account];

        foreach ($preferred as $candidate) {
            if ($candidate) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>|null
     */
    public function routeNotificationForMail($notification): array|null
    {
        $email = $this->deliveryEmail();

        return $email === null ? null : [$email => $this->name];
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
