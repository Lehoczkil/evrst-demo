<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Filament\Resources\Cms\AboutGoals\Pages\EditAboutGoal;
use App\Filament\Resources\Cms\AboutProjects\Pages\CreateAboutProject;
use App\Filament\Resources\Cms\AboutProjects\Pages\EditAboutProject;
use App\Filament\Resources\Cms\Events\Pages\CreateEvent;
use App\Filament\Resources\Cms\Events\Pages\EditEvent;
use App\Filament\Resources\Cms\Mentors\Pages\EditMentor;
use App\Filament\Resources\Cms\Sponsors\Pages\EditSponsor;
use App\Models\Cms\AboutGoal;
use App\Models\Cms\AboutProject;
use App\Models\Cms\Event;
use App\Models\Cms\Mentor;
use App\Models\Cms\Sponsor;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CMS edit forms open with the record's data in them.
 *
 * Everything on these models lives in the `payload` JSON column behind
 * virtual attributes. Filament seeds an edit form from
 * `attributesToArray()`, which doesn't see accessors — so the forms opened
 * blank on records that plainly had data, and saving wrote the blanks back.
 * `App\Filament\Concerns\FillsVirtualAttributes` closes that gap.
 */
class CmsEditFormFillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(CollectionSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();

        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin@evrst.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::where('key', Perm::ROLE_ADMIN)->value('id'),
            'password_changed_at' => now(),
        ]));
    }

    public function test_project_edit_form_is_filled_from_the_payload(): void
    {
        $project = AboutProject::create([
            'title' => 'Helios',
            'description_en' => 'Mid-power rocket to 3 km.',
            'description_hu' => 'Közepes teljesítményű rakéta 3 km-re.',
            'discord_webhook_url' => 'https://discord.com/api/webhooks/1/abc',
            'position' => 1,
        ]);

        Livewire::test(EditAboutProject::class, ['record' => $project->getKey()])
            ->assertFormSet([
                'title' => 'Helios',
                'description_en' => 'Mid-power rocket to 3 km.',
                'description_hu' => 'Közepes teljesítményű rakéta 3 km-re.',
                'discord_webhook_url' => 'https://discord.com/api/webhooks/1/abc',
                'position' => 1,
            ]);
    }

    public function test_saving_a_project_untouched_keeps_every_payload_field(): void
    {
        $project = AboutProject::create([
            'title' => 'Helios',
            'description_en' => 'Mid-power rocket to 3 km.',
            'description_hu' => 'Közepes teljesítményű rakéta 3 km-re.',
            'position' => 1,
        ]);

        Livewire::test(EditAboutProject::class, ['record' => $project->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $project->refresh();

        $this->assertSame('Helios', $project->title);
        $this->assertSame('Mid-power rocket to 3 km.', $project->description_en);
        $this->assertSame('Közepes teljesítményű rakéta 3 km-re.', $project->description_hu);
    }

    public function test_goal_edit_form_is_filled_from_the_payload(): void
    {
        $goal = AboutGoal::create([
            'title_en' => 'Reach 3 km',
            'title_hu' => '3 km elérése',
            'description_en' => 'Certify the airframe first.',
        ]);

        Livewire::test(EditAboutGoal::class, ['record' => $goal->getKey()])
            ->assertFormSet([
                'title_en' => 'Reach 3 km',
                'title_hu' => '3 km elérése',
                'description_en' => 'Certify the airframe first.',
            ]);
    }

    public function test_sponsor_edit_form_is_filled_from_the_payload(): void
    {
        $sponsor = Sponsor::create([
            'name' => 'Acme Aerospace',
            'url' => 'https://acme.test',
            'year' => '2026',
            'description_en' => 'Airframe materials.',
        ]);

        Livewire::test(EditSponsor::class, ['record' => $sponsor->getKey()])
            ->assertFormSet([
                'name' => 'Acme Aerospace',
                'url' => 'https://acme.test',
                'year' => '2026',
                'description_en' => 'Airframe materials.',
            ]);
    }

    /**
     * The upload fields are payload-backed too, so they are hydrated by the
     * same path — and an untouched one has to survive a save rather than be
     * dehydrated back as empty.
     */
    public function test_editing_a_sponsor_keeps_its_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('sponsors/acme.png', 'not-really-a-png');

        $sponsor = Sponsor::create([
            'name' => 'Acme',
            'logo' => 'sponsors/acme.png',
        ]);

        Livewire::test(EditSponsor::class, ['record' => $sponsor->getKey()])
            ->assertFormSet(fn (array $state) => $this->assertSame(
                ['sponsors/acme.png'],
                array_values((array) $state['logo']),
            ))
            ->fillForm(['name' => 'Acme Aerospace'])
            ->call('save')
            ->assertHasNoFormErrors();

        $sponsor->refresh();

        $this->assertSame('Acme Aerospace', $sponsor->name);
        $this->assertSame('sponsors/acme.png', $sponsor->logo);
    }

    public function test_mentor_edit_form_is_filled_from_the_payload(): void
    {
        $mentor = Mentor::create([
            'name' => 'Dr. Kovács',
            'email' => 'mentor@example.test',
        ]);

        Livewire::test(EditMentor::class, ['record' => $mentor->getKey()])
            ->assertFormSet([
                'name' => 'Dr. Kovács',
                'email' => 'mentor@example.test',
            ]);
    }

    public function test_event_edit_form_is_filled_from_the_payload(): void
    {
        $event = Event::create([
            'title_en' => 'Launch day',
            'title_hu' => 'Indítás napja',
            'content_en' => 'Meet at the field at 08:00.',
            'start_at' => now()->addWeek(),
            'status' => 'PUBLISHED',
        ]);

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->assertFormSet([
                'title_en' => 'Launch day',
                'title_hu' => 'Indítás napja',
                'content_en' => 'Meet at the field at 08:00.',
                'status' => 'PUBLISHED',
            ]);
    }

    /**
     * The collection is "past and upcoming events", so a past one has to
     * stay editable. The future-only rule on `start_at` used to apply on
     * edit too, which meant the form refused to save the date it had just
     * filled itself with.
     */
    public function test_a_past_event_can_still_be_edited(): void
    {
        $event = Event::create([
            'title_en' => 'Last year launch',
            'title_hu' => 'Tavalyi indítás',
            'start_at' => now()->subYear(),
            'end_at' => now()->subYear()->addHours(4),
            'status' => 'PUBLISHED',
        ]);

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->fillForm(['title_en' => 'Last year launch (recap)'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Last year launch (recap)', $event->refresh()->title_en);
        $this->assertTrue($event->start_at->isLastYear() || $event->start_at->isPast());
    }

    /** A brand-new event still has to be in the future. */
    public function test_a_new_event_may_not_start_in_the_past(): void
    {
        Livewire::test(CreateEvent::class)
            ->fillForm([
                'title_en' => 'Backdated',
                'title_hu' => 'Visszadátumozott',
                'status' => 'DRAFT',
                'start_at' => now()->subWeek()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['start_at']);
    }

    /**
     * The pickers dehydrate to 'Y-m-d H:i' and SQLite stores a datetime
     * column verbatim, so an unnormalised value sorts as a prefix of a
     * canonical one — a project starting at 00:00 on the 1st fell outside
     * the calendar's whereBetween for its own month.
     */
    public function test_project_dates_are_stored_canonically(): void
    {
        Livewire::test(CreateAboutProject::class)
            ->fillForm([
                'title' => 'Nova',
                'start_at' => '2026-10-01 00:00',
                'end_at' => '2026-11-30 23:59',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = AboutProject::firstOrFail()->getAttributes();

        $this->assertSame('2026-10-01 00:00:00', $stored['start_at']);
        $this->assertSame('2026-11-30 23:59:00', $stored['end_at']);
    }

    /** An end time before the start is still rejected, on either page. */
    public function test_an_event_may_not_end_before_it_starts(): void
    {
        $event = Event::create([
            'title_en' => 'Launch day',
            'title_hu' => 'Indítás napja',
            'start_at' => now()->addWeek(),
            'status' => 'DRAFT',
        ]);

        Livewire::test(EditEvent::class, ['record' => $event->getKey()])
            ->fillForm(['end_at' => now()->addWeek()->subHour()->format('Y-m-d H:i:s')])
            ->call('save')
            ->assertHasFormErrors(['end_at']);
    }
}
