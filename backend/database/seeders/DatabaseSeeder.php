<?php

namespace Database\Seeders;

use App\Auth\Perm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Roles + permissions first so the admin user can be linked to one.
        $this->call([
            RoleSeeder::class,
        ]);

        $adminRole = Role::where('key', Perm::ROLE_ADMIN)->first();

        User::updateOrCreate(
            ['email' => 'admin@evrst.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'role_id' => $adminRole?->id,
                // Existing seeded admin already knows the password — don't
                // pester them with a forced change on next login.
                'password_changed_at' => now(),
            ],
        );

        $this->call([
            CollectionSeeder::class,
            ResourceSeeder::class,
            TeamSeeder::class,
            TaskSeeder::class,
            DemoSeeder::class,
        ]);
    }
}
