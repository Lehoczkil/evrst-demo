<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Read or change a user's role from the shell.
 *
 * Exists as break-glass: the Users resource is admin-only, so an account
 * that is not already an Admin cannot grant itself access through the
 * panel, and the seeded admin@evrst.test is not something a public
 * deployment should keep around (default password).
 */
class UserRole extends Command
{
    protected $signature = 'user:role
                            {email? : The account to change; omit to list everyone}
                            {role? : admin | manager | member}
                            {--force : Allow demoting the last remaining admin}';

    protected $description = "Show or change a user's role";

    public function handle(): int
    {
        $email = $this->argument('email');
        $roleKey = $this->argument('role');

        if ($email === null) {
            return $this->listUsers();
        }

        $user = User::with('role')->where('email', $email)->first();

        if (! $user) {
            $this->components->error("No account with email {$email}.");
            $this->line('  Run `php artisan user:role` with no arguments to list them.');

            return self::FAILURE;
        }

        if ($roleKey === null) {
            $this->line("  {$user->name} <{$user->email}> is currently: " . ($user->role->key ?? 'no role'));

            return self::SUCCESS;
        }

        $role = Role::where('key', $roleKey)->first();

        if (! $role) {
            $this->components->error("No role with key '{$roleKey}'.");
            $this->line('  Available: ' . Role::orderBy('name')->pluck('key')->implode(', '));

            return self::FAILURE;
        }

        $previous = $user->role->key ?? 'no role';

        if ($previous === $role->key) {
            $this->components->info("{$user->name} is already {$role->key} — nothing to do.");

            return self::SUCCESS;
        }

        // Locking everyone out of user management is recoverable only from
        // this command, so make it deliberate rather than a surprise.
        if ($previous === 'admin' && $role->key !== 'admin' && ! $this->option('force')) {
            $remaining = User::whereHas('role', fn ($q) => $q->where('key', 'admin'))
                ->whereKeyNot($user->getKey())
                ->count();

            if ($remaining === 0) {
                $this->components->error('This is the last admin. Demoting it would leave nobody able to manage users or roles.');
                $this->line('  Pass --force if you really mean it.');

                return self::FAILURE;
            }
        }

        $user->forceFill(['role_id' => $role->id])->save();

        $this->components->info("{$user->name} <{$user->email}>: {$previous} → {$role->key}");

        // Perm checks read the role on each request, so this takes effect
        // immediately — but a signed-in user keeps the page they are on.
        $this->line('  Takes effect on their next request; ask them to reload.');

        return self::SUCCESS;
    }

    private function listUsers(): int
    {
        $users = User::with('role')->orderBy('name')->get();

        if ($users->isEmpty()) {
            $this->components->warn('No accounts exist.');

            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Login', 'Role'],
            $users->map(fn (User $u) => [$u->name, $u->email, $u->role->key ?? '—'])->all(),
        );

        $admins = $users->filter(fn (User $u) => ($u->role->key ?? null) === 'admin');

        if ($admins->isEmpty()) {
            $this->components->error('No admin accounts. Nobody can reach Users, Roles or Applications.');
            $this->line('  Fix with: php artisan user:role <email> admin');

            return self::FAILURE;
        }

        $this->line('  ' . $admins->count() . ' admin(s): ' . $admins->pluck('email')->implode(', '));

        return self::SUCCESS;
    }
}
