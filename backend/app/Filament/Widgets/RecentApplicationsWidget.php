<?php

namespace App\Filament\Widgets;

use App\Auth\Perm;
use App\Filament\Resources\MemberApplications\MemberApplicationResource;
use App\Models\MemberApplication;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecentApplicationsWidget extends Widget
{
    protected string $view = 'filament.widgets.recent-applications';

    public function getHeading(): ?string { return __('admin.widgets.recent_apps'); }

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        $u = auth()->user();
        return (bool) ($u?->can(Perm::APPLICATIONS_EDIT) || $u?->can(Perm::APPLICATIONS_ACCEPT));
    }

    /** @return Collection<int, MemberApplication> */
    public function getApplications(): Collection
    {
        return Cache::remember('widgets:recent-applications', 60, fn () => MemberApplication::query()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'name', 'email', 'department', 'status', 'created_at']));
    }

    public function urlFor(MemberApplication $application): string
    {
        return MemberApplicationResource::getUrl('edit', ['record' => $application->id]);
    }
}
