<?php

namespace Tests\Feature;

use App\Jobs\PostDiscordWebhook;
use App\Models\Collection;
use App\Models\MemberApplication;
use App\Models\Resource;
use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Covers the public REST API consumed by the SPA:
 *  - GET /api/resource (list) + /api/resource/{id} (show)
 *  - POST /api/member-applications (Join-us submission)
 */
class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
    }

    public function test_resource_index_returns_collection_records(): void
    {
        $collection = Collection::where('slug', 'events')->firstOrFail();
        Resource::create([
            'collection_id' => $collection->id,
            'payload' => [
                'title' => ['en' => 'Launch day', 'hu' => 'Indítás'],
                'status' => 'PUBLISHED',
            ],
            'position' => 0,
        ]);

        $response = $this->getJson('/api/resource?collectionId=' . $collection->id);

        $response->assertOk();
        $response->assertJsonStructure([['id', 'collectionId', 'payload', 'createdAt', 'updatedAt']]);
        $this->assertNotEmpty($response->json());
    }

    public function test_resource_show_returns_single_record(): void
    {
        $collection = Collection::where('slug', 'events')->firstOrFail();
        $resource = Resource::create([
            'collection_id' => $collection->id,
            'payload' => ['title' => ['en' => 'Single', 'hu' => 'Single']],
            'position' => 0,
        ]);

        $this->getJson('/api/resource/' . $resource->id)
            ->assertOk()
            ->assertJsonPath('id', $resource->id);
    }

    public function test_resource_show_localises_payload_via_x_lang_header(): void
    {
        $collection = Collection::where('slug', 'events')->firstOrFail();
        $resource = Resource::create([
            'collection_id' => $collection->id,
            'payload' => ['title' => ['en' => 'EN title', 'hu' => 'HU cím']],
            'position' => 0,
        ]);

        $this->getJson('/api/resource/' . $resource->id, ['X-Lang' => 'hu'])
            ->assertOk()
            ->assertJsonPath('payload.title', 'HU cím');

        $this->getJson('/api/resource/' . $resource->id, ['X-Lang' => 'en'])
            ->assertOk()
            ->assertJsonPath('payload.title', 'EN title');
    }

    public function test_member_application_post_creates_pending_row(): void
    {
        Bus::fake();

        $this->postJson('/api/member-applications', [
            'email' => 'smoke@example.com',
            'name' => 'Smoke Person',
            'department' => 'Propulsion',
            'why' => 'I want to launch rockets.',
            'languages' => ['English'],
        ])
            ->assertCreated()
            ->assertJsonStructure(['id']);

        $this->assertDatabaseHas('member_applications', [
            'email' => 'smoke@example.com',
            'status' => MemberApplication::STATUS_PENDING,
        ]);

        // Discord webhook is dispatched even when the URL is empty —
        // the job no-ops in that case but the dispatch must happen.
        Bus::assertDispatched(PostDiscordWebhook::class);
    }

    public function test_member_application_post_validates_required_fields(): void
    {
        $this->postJson('/api/member-applications', ['name' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'name']);
    }

    public function test_member_application_post_is_rate_limited(): void
    {
        // Bus::fake() so the 10 fake submissions can't reach the real
        // Discord channel even if the test runs in an environment
        // where DISCORD_WEBHOOK_URL is somehow set (defence in depth
        // against missing phpunit.xml `force="true"`).
        Bus::fake();

        // Throttle key is per-IP; hammer the same endpoint and the
        // 11th call must trip 429.
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/member-applications', [
                'email' => "rl-$i@example.com",
                'name' => "RL $i",
            ])->assertCreated();
        }

        $this->postJson('/api/member-applications', [
            'email' => 'rl-11@example.com',
            'name' => 'RL 11',
        ])->assertStatus(429);
    }
}
