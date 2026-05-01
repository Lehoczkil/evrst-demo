<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill contacts.* permission rows + role pivots. Idempotent.
 *
 * Distribution:
 *  - Admin   → view + create + edit + delete
 *  - Manager → view + create + edit
 *  - Member  → view
 */
return new class extends Migration
{
    private const NEW_PERMS = [
        ['key' => 'contacts.view',   'label' => 'View outer contacts'],
        ['key' => 'contacts.create', 'label' => 'Create outer contacts'],
        ['key' => 'contacts.edit',   'label' => 'Edit outer contacts'],
        ['key' => 'contacts.delete', 'label' => 'Delete outer contacts'],
    ];

    private const ROLE_PERMS = [
        'admin'   => ['contacts.view', 'contacts.create', 'contacts.edit', 'contacts.delete'],
        'manager' => ['contacts.view', 'contacts.create', 'contacts.edit'],
        'member'  => ['contacts.view'],
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

        $roleIds = DB::table('roles')
            ->whereIn('key', array_keys(self::ROLE_PERMS))
            ->pluck('id', 'key');

        foreach (self::ROLE_PERMS as $roleKey => $permKeys) {
            $roleId = $roleIds[$roleKey] ?? null;
            if (! $roleId) continue;
            foreach ($permKeys as $key) {
                $pid = $permissionIds[$key] ?? null;
                if (! $pid) continue;
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
