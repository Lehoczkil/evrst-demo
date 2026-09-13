<?php

namespace Tests\Feature;

use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `/api/*` had no rate limit at all except the join-us POST, and
 * `/api/img` wrote a new file to the app-data volume — the one the SQLite
 * database and every upload share — for each distinct
 * (path, w, h, f, q, fit) tuple. `w` and `h` each accepted any integer
 * from 16 to 3000 and `q` any from 1 to 100, so a stranger with a loop
 * could fill the disk and take the site down with it.
 *
 * Two changes: every public route is throttled, and the transform
 * parameters snap to a fixed ladder so the reachable variant count per
 * image is bounded (ImageController::SIZE_STEPS).
 */
class ApiThrottlingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);
        Storage::fake('public');
        $this->putFixturePng('fixture.png', 400, 300);
    }

    public function test_the_json_api_is_throttled(): void
    {
        // The limiter allows 120/min; walk past it.
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/team/groups')->assertOk();
        }

        $this->getJson('/api/team/groups')->assertStatus(429);
    }

    public function test_the_image_endpoint_is_throttled_on_its_own_budget(): void
    {
        // Images get a separate, higher budget — spending the JSON one
        // must not lock the page's pictures out.
        for ($i = 0; $i < 120; $i++) {
            $this->getJson('/api/team/groups');
        }

        $this->get('/api/img?path=fixture.png&w=200&f=webp')->assertOk();
    }

    /**
     * The heart of the disk-fill: distinct sizes used to mean distinct
     * files. Ten neighbouring widths must now collapse onto one variant.
     */
    public function test_neighbouring_widths_share_one_cache_file(): void
    {
        foreach (range(181, 200) as $width) {
            $this->get("/api/img?path=fixture.png&w={$width}&f=webp")->assertOk();
        }

        $this->assertCount(1, $this->cachedFiles());
    }

    public function test_neighbouring_qualities_share_one_cache_file(): void
    {
        foreach ([76, 78, 80, 82, 84] as $quality) {
            $this->get("/api/img?path=fixture.png&w=200&f=webp&q={$quality}")->assertOk();
        }

        $this->assertCount(1, $this->cachedFiles());
    }

    /** A dpr variant lands on the ladder too, rather than beside it. */
    public function test_dpr_does_not_open_a_second_axis(): void
    {
        $this->get('/api/img?path=fixture.png&w=72&dpr=3&f=webp')->assertOk();
        $this->get('/api/img?path=fixture.png&w=216&f=webp')->assertOk();

        $this->assertCount(1, $this->cachedFiles());
    }

    /** Distinct rungs still produce distinct images — this is not a cap. */
    public function test_separate_rungs_still_render_separately(): void
    {
        $this->get('/api/img?path=fixture.png&w=72&f=webp')->assertOk();
        $this->get('/api/img?path=fixture.png&w=200&f=webp')->assertOk();

        $this->assertCount(2, $this->cachedFiles());
    }

    private function putFixturePng(string $name, int $width, int $height): void
    {
        $im = imagecreatetruecolor($width, $height);
        imagefill($im, 0, 0, imagecolorallocate($im, 200, 50, 50));

        ob_start();
        imagepng($im);
        $bytes = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($name, $bytes);
    }

    /** @return array<int, string> */
    private function cachedFiles(): array
    {
        $root = Storage::disk('public')->path('cache/img');
        if (! is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $entry) {
            if ($entry->isFile() && ! str_ends_with($entry->getFilename(), '.meta.json')) {
                $files[] = $entry->getPathname();
            }
        }

        return $files;
    }
}
