<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Filament\Pages\Dashboard;
use App\Models\ActivityLog;
use App\Models\CalendarEvent;
use App\Models\MemberApplication;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The gates that were missing: the calendar is readable by everyone but
 * writable only by admins, and the dashboard tiles carrying applicant
 * names / the audit feed render for admins alone.
 *
 * A Livewire endpoint is reachable whatever the Blade rendered, so every
 * assertion here calls the method directly rather than clicking a button.
 */
class AdminAccessGatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        // Creating a calendar event posts a Discord webhook.
        Bus::fake();
    }

    private function event(): CalendarEvent
    {
        return CalendarEvent::create([
            'title' => 'Static fire',
            'start_at' => now()->addWeek(),
            'end_at' => now()->addWeek()->addHour(),
            'color' => '#0ea5e9',
        ]);
    }

    public function test_a_member_can_read_the_calendar(): void
    {
        $this->actingAs($this->makeMember())->get('/admin/calendar')->assertOk();
    }

    public function test_a_member_cannot_open_the_create_modal(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(Calendar::class)
            ->call('openCreateModal', now()->addDay()->toDateString())
            ->assertForbidden();
    }

    public function test_a_member_cannot_save_an_event(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(Calendar::class)
            ->set('eventTitle', 'Injected')
            ->set('eventStart', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('eventEnd', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
            ->call('saveEvent')
            ->assertForbidden();

        $this->assertDatabaseMissing('calendar_events', ['title' => 'Injected']);
    }

    public function test_a_member_cannot_delete_an_event(): void
    {
        $event = $this->event();

        Livewire::actingAs($this->makeMember())
            ->test(Calendar::class)
            ->set('editingId', $event->id)
            ->call('deleteEvent')
            ->assertForbidden();

        $this->assertDatabaseHas('calendar_events', ['id' => $event->id]);
    }

    public function test_a_member_cannot_open_someone_elses_event_for_editing(): void
    {
        $event = $this->event();

        Livewire::actingAs($this->makeMember())
            ->test(Calendar::class)
            ->call('openEditModal', $event->id)
            ->assertForbidden();
    }

    public function test_an_admin_can_still_create_an_event(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(Calendar::class)
            ->call('openCreateModal', now()->addDay()->toDateString())
            ->set('eventTitle', 'Static fire')
            ->call('saveEvent')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('calendar_events', ['title' => 'Static fire']);
    }

    public function test_the_dashboard_hides_applicant_names_and_the_audit_feed_from_a_member(): void
    {
        MemberApplication::create([
            'id' => (string) Str::ulid(),
            'name' => 'Titkos Jelentkező',
            'email' => 'titkos@example.test',
            'status' => MemberApplication::STATUS_PENDING,
        ]);
        ActivityLog::create([
            'event' => 'updated',
            'subject_type' => MemberApplication::class,
            'subject_id' => '1',
            'subject_label' => 'Audit feed entry',
        ]);

        $this->actingAs($this->makeMember())
            ->get('/admin')
            ->assertOk()
            ->assertDontSee('Titkos Jelentkező')
            ->assertDontSee('Audit feed entry');
    }

    public function test_the_dashboard_still_shows_both_to_an_admin(): void
    {
        MemberApplication::create([
            'id' => (string) Str::ulid(),
            'name' => 'Titkos Jelentkező',
            'email' => 'titkos@example.test',
            'status' => MemberApplication::STATUS_PENDING,
        ]);
        ActivityLog::create([
            'event' => 'updated',
            'subject_type' => MemberApplication::class,
            'subject_id' => '1',
            'subject_label' => 'Audit feed entry',
        ]);

        $this->actingAs($this->makeAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Titkos Jelentkező')
            ->assertSee('Audit feed entry');
    }

    public function test_the_dashboard_offers_no_quick_action_a_member_cannot_follow(): void
    {
        $member = $this->makeMember();

        $actions = Livewire::actingAs($member)->test(Dashboard::class)->instance()->getQuickActions();

        foreach ($actions as $action) {
            $this->actingAs($member)
                ->get($action['url'])
                ->assertOk();
        }
    }
}
