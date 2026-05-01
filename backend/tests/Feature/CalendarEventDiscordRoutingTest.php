<?php

namespace Tests\Feature;

use App\Filament\Pages\Calendar;
use App\Jobs\PostDiscordWebhook;
use App\Models\Cms\AboutProject;
use App\Models\Collection;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarEventDiscordRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
    }

    public function test_creating_event_without_project_dispatches_webhook_with_null_override(): void
    {
        Bus::fake();
        $this->actingAs($this->makeAdmin());

        Livewire::test(Calendar::class)
            ->set('eventTitle', 'No project event')
            ->set('eventStart', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('eventEnd', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
            ->set('eventProjectId', null)
            ->call('saveEvent');

        Bus::assertDispatched(
            PostDiscordWebhook::class,
            fn (PostDiscordWebhook $job) => $job->webhookUrl === null,
        );
    }

    public function test_creating_event_with_project_passes_project_webhook_url(): void
    {
        Bus::fake();
        $this->actingAs($this->makeAdmin());

        $project = AboutProject::create([
            'collection_id' => Collection::where('slug', 'about-projects')->firstOrFail()->id,
            'payload' => [
                'title' => ['en' => 'Project A', 'hu' => 'Projekt A'],
                'discord_webhook_url' => 'https://discord.com/api/webhooks/123/abc',
            ],
            'position' => 0,
        ]);

        Livewire::test(Calendar::class)
            ->set('eventTitle', 'Project event')
            ->set('eventStart', now()->addDay()->format('Y-m-d\TH:i'))
            ->set('eventEnd', now()->addDay()->addHour()->format('Y-m-d\TH:i'))
            ->set('eventProjectId', $project->id)
            ->call('saveEvent');

        Bus::assertDispatched(
            PostDiscordWebhook::class,
            fn (PostDiscordWebhook $job) => $job->webhookUrl === 'https://discord.com/api/webhooks/123/abc',
        );
    }
}
