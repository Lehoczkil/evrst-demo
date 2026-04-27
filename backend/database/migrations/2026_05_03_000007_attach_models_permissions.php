<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The new models.* permissions were added to Perm::catalog() at the
 * same time as the OnshapeModel resource, but the seeded Admin /
 * Manager rows already exist in the DB. Without re-running the seeder
 * (which would also touch other resources) the pivot rows are
 * missing, so canCreate() returns false → 403 on /onshape-models/create.
 *
 * Backfill: insert the new permission rows if missing, and attach
 * them to the Admin and Manager roles. Idempotent.
 */
return new class extends Migration
{
    private const NEW_PERMS = [
        ['key' => 'models.view',   'label' => 'View 3D models'],
        ['key' => 'models.create', 'label' => 'Create 3D models'],
        ['key' => 'models.edit',   'label' => 'Edit 3D models'],
        ['key' => 'models.delete', 'label' => 'Delete 3D models'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::NEW_PERMS as $entry) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $entry['key']],
                ['label' => $entry['label'], 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column(self::NEW_PERMS, 'key'))
            ->pluck('id', 'key');

        // Both Admin and Manager get the full models.* set; Member stays
        // read-only via the role-key check on the resource.
        $roles = DB::table('roles')->whereIn('key', ['admin', 'manager'])->pluck('id', 'key');
        foreach ($roles as $roleId) {
            foreach ($permissionIds as $pid) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $pid],
                    [],
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('key', array_column(self::NEW_PERMS, 'key'))
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
