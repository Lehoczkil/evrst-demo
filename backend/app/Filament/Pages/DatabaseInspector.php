<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-only read-only schema browser. Shows the list of tables on
 * the left, columns / indexes / foreign keys / row count on the
 * right. No data preview, no editing — just structure. Reads come
 * from Laravel's Schema introspection API which works across
 * SQLite / MySQL / Postgres.
 */
class DatabaseInspector extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Advanced';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.database-inspector';

    public ?string $selectedTable = null;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.db_inspector.title');
    }

    public function getTitle(): string
    {
        return __('admin.db_inspector.title');
    }

    public function getHeading(): string
    {
        return __('admin.db_inspector.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.db_inspector.subtitle');
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $tables = $this->tableNames();
        $this->selectedTable = $this->selectedTable ?? ($tables[0] ?? null);
    }

    public function selectTable(string $name): void
    {
        if (in_array($name, $this->tableNames(), true)) {
            $this->selectedTable = $name;
        }
    }

    /** @return array<int, string> */
    private function tableNames(): array
    {
        $names = array_map(
            fn ($t) => is_array($t) ? ($t['name'] ?? '') : (string) $t,
            Schema::getTables(),
        );
        $names = array_values(array_filter($names));
        sort($names);
        return $names;
    }

    /** @return array<int, array{name: string, rows: int}> */
    public function getTablesIndex(): array
    {
        // A COUNT(*) per table on every render — including every Livewire
        // round trip on this page — is the most expensive thing here and
        // the least time-sensitive. A minute of staleness on a row count
        // an admin is browsing costs nothing.
        return Cache::remember('db-inspector:table-counts', 60, fn () => array_map(
            function (string $name) {
                try {
                    $rows = (int) DB::table($name)->count();
                } catch (\Throwable) {
                    $rows = 0;
                }

                return ['name' => $name, 'rows' => $rows];
            },
            $this->tableNames(),
        ));
    }

    /**
     * @return array{
     *     name: string,
     *     row_count: int,
     *     columns: array<int, array<string, mixed>>,
     *     indexes: array<int, array<string, mixed>>,
     *     foreign_keys: array<int, array<string, mixed>>,
     * }|null
     */
    public function getSelectedTableDetails(): ?array
    {
        $name = $this->selectedTable;
        if (! $name || ! Schema::hasTable($name)) {
            return null;
        }

        try {
            $rowCount = (int) DB::table($name)->count();
        } catch (\Throwable) {
            $rowCount = 0;
        }

        return [
            'name' => $name,
            'row_count' => $rowCount,
            'columns' => Schema::getColumns($name),
            'indexes' => Schema::getIndexes($name),
            'foreign_keys' => Schema::getForeignKeys($name),
        ];
    }

    public function getDriver(): string
    {
        return DB::connection()->getDriverName();
    }
}
