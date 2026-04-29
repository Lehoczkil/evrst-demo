<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Filament's `DatabaseNotifications` widget filters with
 * `->where('data->format', 'filament')`, which Laravel compiles to
 * `"data"->>'format'` on Postgres. That operator only exists on
 * `json` / `jsonb` columns — the default `text()` type from Laravel's
 * `notifications:table` blueprint makes Postgres throw SQLSTATE
 * [42883] "operator does not exist: text -> unknown" the first time
 * the bell loads.
 *
 * SQLite happily applies the `json_extract` function regardless of
 * declared column type, so this only matters on Postgres / MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE json USING data::json');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE notifications MODIFY data JSON');
        }
        // SQLite: no-op. JSON1 functions work on any TEXT column.
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE notifications ALTER COLUMN data TYPE text USING data::text');
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE notifications MODIFY data TEXT');
        }
    }
};
