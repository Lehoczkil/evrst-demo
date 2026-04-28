<?php

namespace App\Filament\Resources\Bugs\Pages;

use App\Auth\Perm;
use App\Filament\Resources\Bugs\BugReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBugReport extends EditRecord
{
    protected static string $resource = BugReportResource::class;

    public function getTitle(): string { return __('admin.resources.bug_report.s'); }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn () => auth()->user()?->can(Perm::BUGS_DELETE) ?? false),
        ];
    }
}
