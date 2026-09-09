<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `tasks.progress` lets an assignee (or the supervisor) open their own
 * task, attach proof and move it along, without granting the blanket
 * `tasks.edit` that rewrites anybody's task. RoleSeeder runs once, so the
 * key has to be backfilled onto the roles that already exist — all three
 * of them here: Member is the role it was added for, and Admin / Manager
 * would otherwise be missing a key their catalogue claims they hold.
 *
 * Idempotent, same shape as 2026_05_03_000007_attach_models_permissions.
 */
return new class extends Migration
{
    private const KEY = 'tasks.progress';

    private const LABEL = 'Progress own tasks';

    public function up(): void
    {
        $now = now();

        DB::table('permissions')->updateOrInsert(
            ['key' => self::KEY],
            ['label' => self::LABEL, 'updated_at' => $now, 'created_at' => $now],
        );

        $permissionId = DB::table('permissions')->where('key', self::KEY)->value('id');

        if (! $permissionId) {
            return;
        }

        $roleIds = DB::table('roles')->whereIn('key', ['admin', 'manager', 'member'])->pluck('id');

        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                [],
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('key', self::KEY)->value('id');

        if (! $permissionId) {
            return;
        }

        DB::table('permission_role')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('id', $permissionId)->delete();
    }
};
