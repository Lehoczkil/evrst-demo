<?php

namespace App\Filament\Resources\Bugs\Pages;

use App\Filament\Resources\Bugs\BugReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBugReport extends CreateRecord
{
    protected static string $resource = BugReportResource::class;

    public function getTitle(): string { return __('admin.bugs.report_new'); }

    public function getHeading(): string { return __('admin.bugs.report_new'); }

    public function getSubheading(): ?string { return __('admin.bugs.report_sub'); }

    /**
     * After submission, take the user back to the list — they're done,
     * not editing the freshly-filed report.
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
