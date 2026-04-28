<?php

namespace App\Filament\Widgets;

use App\Models\CalendarEvent;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

/**
 * Mission-console countdown strip on the dashboard.
 *
 * Shows T-{d}:{h}:{m}:{s} to the next CalendarEvent, or — when the team
 * is between launches — counts UP from the most recent past event
 * (e.g. "LAST EVENT +12d") so the slot stays alive.
 */
class LaunchCountdownWidget extends Widget
{
    protected string $view = 'filament.widgets.launch-countdown';

    protected int|string|array $columnSpan = 'full';

    public function getColumnSpan(): int|string|array { return 'full'; }

    protected static ?int $sort = -10;

    public function getNextEvent(): ?CalendarEvent
    {
        return CalendarEvent::query()
            ->where('start_at', '>=', now())
            ->orderBy('start_at')
            ->first();
    }

    public function getLastEvent(): ?CalendarEvent
    {
        return CalendarEvent::query()
            ->where('start_at', '<', now())
            ->orderByDesc('start_at')
            ->first();
    }

    /** @return array<string, mixed> */
    public function getMissionState(): array
    {
        $next = $this->getNextEvent();
        if ($next && $next->start_at) {
            return [
                'mode'    => 'countdown',
                'target'  => $next->start_at->copy()->utc()->toIso8601String(),
                'title'   => $next->title,
                'sub'     => trim(collect([$next->location, $next->start_at->format('Y-m-d H:i')])->filter()->join(' · ')),
                'standby' => false,
            ];
        }

        $last = $this->getLastEvent();
        if ($last && $last->start_at) {
            return [
                'mode'    => 'sincelast',
                'target'  => $last->start_at->copy()->utc()->toIso8601String(),
                'title'   => $last->title,
                'sub'     => 'last event · ' . $last->start_at->format('Y-m-d'),
                'standby' => true,
            ];
        }

        return [
            'mode'    => 'standby',
            'target'  => Carbon::now()->utc()->toIso8601String(),
            'title'   => 'STANDBY',
            'sub'     => 'no events scheduled',
            'standby' => true,
        ];
    }
}
