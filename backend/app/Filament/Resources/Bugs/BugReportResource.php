<?php

namespace App\Filament\Resources\Bugs;

use App\Auth\Perm;
use App\Filament\Resources\Bugs\Pages\CreateBugReport;
use App\Filament\Resources\Bugs\Pages\EditBugReport;
use App\Filament\Resources\Bugs\Pages\ListBugReports;
use App\Filament\Resources\Bugs\Schemas\BugReportForm;
use App\Filament\Resources\Bugs\Tables\BugReportsTable;
use App\Models\BugReport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BugReportResource extends Resource
{
    protected static ?string $model = BugReport::class;

    /**
     * Pin the slug so the route names stay
     * `filament.admin.resources.bug-reports.{index,create,edit}` even
     * though the resource lives in a `Bugs/` subnamespace. The topbar
     * "Report a bug" shortcut depends on this name.
     */
    protected static ?string $slug = 'bug-reports';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBugAnt;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|\UnitEnum|null $navigationGroup = 'Advanced';

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string { return __('admin.resources.bug_report.p'); }
    public static function getModelLabel(): string { return __('admin.resources.bug_report.s'); }
    public static function getPluralModelLabel(): string { return __('admin.resources.bug_report.p'); }

    /**
     * Anyone with bugs.view (everyone, by default) sees the management
     * page. Reporting is the same gate — Filament's create form is
     * available to anyone with bugs.report.
     */
    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Perm::BUGS_VIEW) ?? false;
    }
    public static function canCreate(): bool { return auth()->user()?->can(Perm::BUGS_REPORT) ?? false; }

    /**
     * Anyone with triage may edit. Reporters can also edit their own
     * report while it is still open (so they can add a screenshot or
     * fix a typo) but cannot change status / assignee — those fields
     * are gated on the form itself.
     */
    public static function canEdit($record): bool
    {
        $u = auth()->user();
        if (! $u) return false;
        if ($u->can(Perm::BUGS_TRIAGE)) return true;
        return $record?->reporter_id === $u->id && $record->isOpen();
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can(Perm::BUGS_DELETE) ?? false;
    }
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(Perm::BUGS_DELETE) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return BugReportForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BugReportsTable::configure($table);
    }

    /**
     * Members see only their own reports. Triagers see everything so
     * the management page is genuinely useful.
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $q = parent::getEloquentQuery();
        $u = auth()->user();
        if ($u && ! $u->can(Perm::BUGS_TRIAGE)) {
            $q->where('reporter_id', $u->id);
        }
        return $q;
    }

    /** Header badge — count of currently-open bugs (admin/manager only). */
    public static function getNavigationBadge(): ?string
    {
        $u = auth()->user();
        if (! $u || ! $u->can(Perm::BUGS_TRIAGE)) return null;
        $count = \Illuminate\Support\Facades\Cache::remember(
            'bugs:open-count',
            60,
            fn () => BugReport::query()->whereIn('status', BugReport::openStatuses())->count(),
        );
        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBugReports::route('/'),
            'create' => CreateBugReport::route('/create'),
            'edit'   => EditBugReport::route('/{record}/edit'),
        ];
    }
}
