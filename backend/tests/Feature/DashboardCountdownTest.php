<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\CalendarEvent;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The dashboard countdown, and the timezone assumption underneath it.
 *
 * Every datetime a person types into this panel is a local wall clock:
 * "the meeting is at 20:00" means 20:00 here. Eloquent reads a stored
 * datetime back in `config('app.timezone')`, so while that was UTC a
 * 20:00 event became 20:00Z — two hours later than anyone meant. The
 * counter said 33 minutes to go for a meeting that had started an hour
 * and a half earlier, and the same skew decided which event counted as
 * "next" at all.
 */
class DashboardCountdownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Bus::fake();
    }

    public function test_the_app_runs_on_local_time(): void
    {
        $this->assertSame('Europe/Budapest', config('app.timezone'));
    }

    public function test_the_countdown_target_is_the_instant_the_admin_typed(): void
    {
        $this->actingAs($this->makeAdmin());

        // What the picker writes: a bare wall clock, no offset.
        $event = $this->eventAt('2026-12-24 18:30:00');

        $state = (new Dashboard())->getMissionState();

        $this->assertSame('countdown', $state['mode']);

        // Read the target back the way a browser would, then look at it in
        // the timezone the admin was standing in. It has to be 18:30 again.
        $asSeenByTheBrowser = Carbon::parse($state['target'])->setTimezone('Europe/Budapest');

        $this->assertSame('2026-12-24 18:30', $asSeenByTheBrowser->format('Y-m-d H:i'));
        $this->assertSame($event->start_at->format('Y-m-d H:i'), $asSeenByTheBrowser->format('Y-m-d H:i'));
    }

    public function test_the_countdown_and_the_subtitle_cannot_disagree(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->eventAt('2026-12-24 18:30:00');
        $state = (new Dashboard())->getMissionState();

        // The subtitle prints the stored wall clock; the counter runs to the
        // target. The bug was visible precisely because these two disagreed.
        $this->assertStringContainsString('2026-12-24 18:30', $state['sub']);
        $this->assertSame(
            '18:30',
            Carbon::parse($state['target'])->setTimezone('Europe/Budapest')->format('H:i'),
        );
    }

    public function test_an_event_that_has_already_started_is_not_the_next_one(): void
    {
        $this->actingAs($this->makeAdmin());

        // Deliberately NOT now()->subMinutes(90): that moves with whatever
        // config('app.timezone') says, so it would agree with itself under
        // any setting. A person's clock does not. This is the wall clock
        // an admin in Budapest would have been looking at.
        $this->eventAt($this->localWallClock('-90 minutes'), 'Already started');
        $this->eventAt($this->localWallClock('+2 days'), 'The real next one');

        $state = (new Dashboard())->getMissionState();

        $this->assertSame('The real next one', $state['title']);
    }

    public function test_with_nothing_upcoming_it_falls_back_to_the_last_one(): void
    {
        $this->actingAs($this->makeAdmin());

        $this->eventAt($this->localWallClock('-3 days'), 'Gone by');

        $state = (new Dashboard())->getMissionState();

        $this->assertSame('sincelast', $state['mode']);
        $this->assertSame('Gone by', $state['title']);
    }

    /** The wall clock a person in Budapest sees, whatever the app thinks. */
    private function localWallClock(string $offset): string
    {
        return (new \DateTime('now', new \DateTimeZone('Europe/Budapest')))
            ->modify($offset)
            ->format('Y-m-d H:i:s');
    }

    private function eventAt(string $wallClock, string $title = 'Meeting'): CalendarEvent
    {
        return CalendarEvent::create([
            'user_id' => auth()->id(),
            'title' => $title,
            'start_at' => $wallClock,
            'all_day' => false,
            'color' => '#0ea5e9',
        ]);
    }
}
