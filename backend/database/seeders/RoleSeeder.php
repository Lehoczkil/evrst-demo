<?php

namespace Database\Seeders;

use App\Auth\Perm;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Permissions — single source of truth is Perm::catalog().
        foreach (Perm::catalog() as $entry) {
            Permission::updateOrCreate(
                ['key' => $entry['key']],
                ['label' => $entry['label']],
            );
        }

        $roles = [
            ['key' => Perm::ROLE_ADMIN,   'name' => 'Admin',   'permissions' => Perm::adminPermissions()],
            ['key' => Perm::ROLE_MANAGER, 'name' => 'Manager', 'permissions' => Perm::managerPermissions()],
            ['key' => Perm::ROLE_MEMBER,  'name' => 'Member',  'permissions' => Perm::memberPermissions()],
        ];

        foreach ($roles as $entry) {
            $role = Role::updateOrCreate(
                ['key' => $entry['key']],
                ['name' => $entry['name']],
            );

            $permissionIds = Permission::whereIn('key', $entry['permissions'])->pluck('id')->all();
            $role->permissions()->sync($permissionIds);
        }
    }
}
