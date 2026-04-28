<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill the new bugs.* permission rows + role pivots. Idempotent.
 *
 * Distribution:
 *  - Admin   → report + view + triage + delete
 *  - Manager → report + view + triage          (no delete)
 *  - Member  → report + view                   (cannot triage / delete)
 */
return new class extends Migration
{
    private const NEW_PERMS = [
        ['key' => 'bugs.report', 'label' => 'Report bugs'],
        ['key' => 'bugs.view',   'label' => 'View bug reports'],
        ['key' => 'bugs.triage', 'label' => 'Triage / resolve bug reports'],
        ['key' => 'bugs.delete', 'label' => 'Delete bug reports'],
    ];

    private const ROLE_PERMS = [
        'admin'   => ['bugs.report', 'bugs.view', 'bugs.triage', 'bugs.delete'],
        'manager' => ['bugs.report', 'bugs.view', 'bugs.triage'],
        'member'  => ['bugs.report', 'bugs.view'],
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
