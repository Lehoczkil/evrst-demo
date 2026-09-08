<?php

namespace Tests;

use App\Auth\Perm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a user attached to one of the seeded roles. Caller passes
     * the role key — `Perm::ROLE_ADMIN` / `ROLE_MANAGER` / `ROLE_MEMBER`
     * — and gets back a user that's already past the first-login
     * password change. Tests use this everywhere instead of factory()
     * so the role plumbing stays exercised.
     */
    protected function makeUser(string $roleKey, array $overrides = []): User
    {
        $role = Role::where('key', $roleKey)->firstOrFail();

        return User::create([
            'name' => $overrides['name'] ?? ucfirst($roleKey) . ' User',
            'email' => $overrides['email'] ?? $roleKey . '-' . uniqid() . '@example.test',
            'password' => Hash::make($overrides['password'] ?? 'test1234'),
            'role_id' => $role->id,
            // array_key_exists, not `??` — a caller that explicitly passes
            // null wants a user still inside the first-login gate.
            'password_changed_at' => array_key_exists('password_changed_at', $overrides)
                ? $overrides['password_changed_at']
                : now(),
        ]);
    }

    protected function makeAdmin(array $overrides = []): User
    {
        return $this->makeUser(Perm::ROLE_ADMIN, $overrides);
    }

    protected function makeManager(array $overrides = []): User
    {
        return $this->makeUser(Perm::ROLE_MANAGER, $overrides);
    }

    protected function makeMember(array $overrides = []): User
    {
        return $this->makeUser(Perm::ROLE_MEMBER, $overrides);
    }
}
