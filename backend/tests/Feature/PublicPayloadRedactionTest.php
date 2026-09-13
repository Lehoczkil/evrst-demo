<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Resource;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * `GET /api/resource` is unauthenticated, returns every row when no
 * `collectionId` is given, and used to emit `payload` verbatim.
 *
 * Two admin forms write things into that payload that the public must
 * not have: `AboutProject` stores a `discord_webhook_url` — a bearer
 * credential for posting to the team's channel — and `Mentor` stores an
 * `email` the site itself only ever renders the domain of.
 *
 * The other half of this test is the part that is easy to get wrong: the
 * SPA reads a lot of this payload, so the redaction must leave every key
 * it consumes alone. The "still serves" cases below are that contract.
 */
class PublicPayloadRedactionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();
    }

    private function row(string $slug, array $payload): Resource
    {
        return Resource::create([
            'collection_id' => Collection::where('slug', $slug)->firstOrFail()->id,
            'payload' => $payload,
            'position' => 0,
        ]);
    }

    public function test_a_projects_discord_webhook_never_reaches_the_public_api(): void
    {
        $this->row('about-projects', [
            'title' => 'Vehicle One',
            'description' => ['en' => 'A rocket.', 'hu' => 'Egy rakéta.'],
            'discord_webhook_url' => 'https://discord.com/api/webhooks/123/supersecret',
        ]);

        $response = $this->getJson('/api/resource');

        $response->assertOk();
        $response->assertJsonMissing(['discord_webhook_url' => 'https://discord.com/api/webhooks/123/supersecret']);
        $this->assertStringNotContainsString('supersecret', $response->getContent());
        $this->assertStringNotContainsString('discord_webhook_url', $response->getContent());
    }

    public function test_a_mentors_address_is_reduced_to_its_domain(): void
    {
        $this->row('mentors', [
            'name' => 'Dr Example',
            'email' => 'jane.example@bme.hu',
            'photo' => 'mentors/jane.png',
        ]);

        $response = $this->getJson('/api/resource');

        $response->assertOk();
        $this->assertStringNotContainsString('jane.example@bme.hu', $response->getContent());

        // The mentors band renders the domain, so that much must survive.
        // (Pick the row by name — migrations seed other rows into the store.)
        $payload = collect($response->json())
            ->firstWhere('payload.name', 'Dr Example')['payload'];
        $this->assertSame('bme.hu', $payload['email_domain']);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertSame('Dr Example', $payload['name']);
    }

    /** A secret nested anywhere in the tree, not just at the top. */
    public function test_redaction_reaches_into_nested_payloads(): void
    {
        $this->row('pages', [
            'name' => 'contact',
            'title' => 'Contact',
            'data' => ['integrations' => ['webhook_url' => 'https://hooks.example/abc', 'api_key' => 'k-123']],
        ]);

        $content = $this->getJson('/api/resource')->assertOk()->getContent();

        $this->assertStringNotContainsString('hooks.example', $content);
        $this->assertStringNotContainsString('k-123', $content);
    }

    /**
     * The hero and rocket copy is walked by dotted path in the SPA
     * (composables/useHomeCopy.ts), so the whole subtree has to come
     * through untouched.
     */
    public function test_the_home_copy_view_still_serves_every_key(): void
    {
        $this->row('views', [
            'name' => 'home-copy',
            'hero' => [
                'eyebrow' => ['en' => 'Escape velocity', 'hu' => 'Szökési sebesség'],
                'title1' => ['en' => 'Build', 'hu' => 'Építs'],
                'says' => [['en' => 'One', 'hu' => 'Egy']],
            ],
            'rocket' => [
                'title' => ['en' => 'Vehicle', 'hu' => 'Rakéta'],
                'specs' => [['label' => ['en' => 'Height', 'hu' => 'Magasság'], 'value' => '3.2', 'unit' => 'm']],
            ],
        ]);

        $payload = collect($this->getJson('/api/resource?lang=en')->assertOk()->json())
            ->last(fn (array $row) => ($row['payload']['name'] ?? null) === 'home-copy')['payload'];

        $this->assertSame('Escape velocity', $payload['hero']['eyebrow']);
        $this->assertSame('Build', $payload['hero']['title1']);
        $this->assertSame(['One'], $payload['hero']['says']);
        $this->assertSame('Vehicle', $payload['rocket']['title']);
        $this->assertSame('Height', $payload['rocket']['specs'][0]['label']);
        $this->assertSame('m', $payload['rocket']['specs'][0]['unit']);
    }

    /** Everything else the SPA reads out of the CMS collections. */
    public function test_the_public_keys_the_spa_reads_are_untouched(): void
    {
        $this->row('events', [
            'title' => ['en' => 'Launch day', 'hu' => 'Indítás'],
            'content' => ['en' => 'Come along.', 'hu' => 'Gyere el.'],
            'start_at' => '2026-10-01 10:00:00',
            'date' => 'October 2026',
            'status' => 'PUBLISHED',
        ]);
        $this->row('sponsors', [
            'name' => 'Acme',
            'url' => 'https://acme.test',
            'year' => 2026,
            'description' => ['en' => 'Gold', 'hu' => 'Arany'],
            'logo' => 'sponsors/acme.png',
        ]);
        $this->row('about-goals', [
            'title' => ['en' => 'Fly', 'hu' => 'Repülni'],
            'description' => ['en' => 'High.', 'hu' => 'Magasra.'],
        ]);
        $this->row('views', ['name' => 'about', 'content' => ['en' => 'About us.', 'hu' => 'Rólunk.']]);

        $rows = collect($this->getJson('/api/resource?lang=en')->assertOk()->json())
            ->keyBy(fn (array $row) => $row['payload']['name'] ?? $row['payload']['title']);

        $event = $rows['Launch day']['payload'];
        $this->assertSame('Come along.', $event['content']);
        $this->assertSame('2026-10-01 10:00:00', $event['start_at']);
        $this->assertSame('October 2026', $event['date']);

        $sponsor = $rows['Acme']['payload'];
        $this->assertSame('https://acme.test', $sponsor['url']);
        $this->assertSame(2026, $sponsor['year']);
        $this->assertSame('Gold', $sponsor['description']);
        $this->assertStringContainsString('sponsors/acme.png', $sponsor['logo']);

        $this->assertSame('High.', $rows['Fly']['payload']['description']);
        $this->assertSame('About us.', $rows['about']['payload']['content']);
    }

    /** The `where[payload][name]` lookups the SPA routes on must still match. */
    public function test_the_name_filter_still_resolves_a_view(): void
    {
        $this->row('views', ['name' => 'about', 'content' => ['en' => 'About us.', 'hu' => 'Rólunk.']]);

        $this->getJson('/api/resource?' . http_build_query([
            'where' => ['payload' => ['path' => ['name'], 'equals' => 'about']],
        ]))->assertOk()->assertJsonCount(1);
    }
}
