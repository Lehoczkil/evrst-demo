<?php

namespace App\Filament\Resources\Bugs\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Bugs\BugReportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBugReports extends ListRecords
{
    protected static string $resource = BugReportResource::class;

    public function getTitle(): string { return __('admin.resources.bug_report.p'); }

    public function getHeading(): string { return __('admin.resources.bug_report.p'); }

    public function getSubheading(): ?string
    {
        return auth()->user()?->can(Perm::BUGS_TRIAGE)
            ? __('admin.bugs.list_sub_triager')
            : __('admin.bugs.list_sub_reporter');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.bugs.report_new'))
                ->icon('heroicon-o-bug-ant'),
        ];
    }
}
