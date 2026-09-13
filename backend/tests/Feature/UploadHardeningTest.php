<?php

namespace Tests\Feature;

use App\Filament\Resources\Bugs\Pages\CreateBugReport;
use App\Filament\Support\Uploads;
use App\Models\BugReport;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Stored XSS through the panel's uploads.
 *
 * Two bugs, one payload. `FileUpload::image()` validates `image/*`, which
 * `image/svg+xml` satisfies — and an SVG is a scriptable document, not a
 * picture. Filament then stored the file as `{ulid}.{client extension}`,
 * so even a file that passed the MIME check could be parked under an
 * extension of the uploader's choosing and served back as markup from
 * our own origin, where it reaches the session cookie and the panel.
 *
 * App\Filament\Support\Uploads closes both: raster-only allow-lists, and
 * a stored extension re-derived from the bytes.
 */
class UploadHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();
        Storage::fake('public');
    }

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.cookie)</script></svg>';

    public function test_the_stored_extension_comes_from_the_bytes_not_the_filename(): void
    {
        // A real PNG announcing itself as something the browser parses.
        $file = UploadedFile::fake()->image('rocket.png');
        $renamed = new UploadedFile($file->getRealPath(), 'rocket.html', 'text/html', null, true);

        $this->assertStringEndsWith('.png', Uploads::storedName($renamed));
    }

    public function test_an_svg_never_keeps_an_executable_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('payload.svg', self::SVG);

        // Whatever the sniffer calls it, it must not be stored as
        // something the browser will parse and run.
        $this->assertStringEndsWith('.bin', Uploads::storedName($file));
    }

    public function test_unknown_bytes_land_on_a_neutral_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('firmware.bin', random_bytes(64));

        $this->assertMatchesRegularExpression('/\.[a-z0-9]+$/', Uploads::storedName($file));
        $this->assertStringNotContainsString('firmware', Uploads::storedName($file));
    }

    /** The allow-lists are what the forms hand to `mimetypes:`. */
    public function test_no_allow_list_admits_svg(): void
    {
        $this->assertNotContains('image/svg+xml', Uploads::IMAGE_TYPES);
        $this->assertNotContains('image/svg+xml', Uploads::PHONE_IMAGE_TYPES);
    }

    /**
     * End to end on the widest-open upload in the panel — anyone signed
     * in can file a bug report, members included.
     */
    public function test_a_member_cannot_attach_an_svg_to_a_bug_report(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(CreateBugReport::class)
            ->set('data.title', 'Broken button')
            ->set('data.description', 'It does nothing.')
            ->set('data.screenshots', [
                UploadedFile::fake()->createWithContent('payload.svg', self::SVG),
            ])
            ->call('create')
            ->assertHasFormErrors(['screenshots']);

        $this->assertDatabaseMissing('bug_reports', ['title' => 'Broken button']);
    }

    /**
     * The other half of the contract: a real screenshot still goes
     * through, and lands under an extension matching its bytes.
     *
     * The misnamed-file case is asserted against Uploads::storedName()
     * above rather than here — Livewire's test harness derives the
     * uploaded MIME type from the filename, so a `.html` name would be
     * rejected by validation in the test long before it could
     * demonstrate anything about the stored name.
     */
    public function test_a_member_can_still_attach_a_screenshot(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(CreateBugReport::class)
            ->set('data.title', 'Broken button')
            ->set('data.description', 'It does nothing.')
            ->set('data.screenshots', [UploadedFile::fake()->image('shot.png')])
            ->call('create')
            ->assertHasNoFormErrors();

        $stored = BugReport::where('title', 'Broken button')->firstOrFail()->screenshots;

        $this->assertIsArray($stored);
        $this->assertCount(1, $stored);
        $this->assertStringEndsWith('.png', $stored[0]);
        Storage::disk('public')->assertExists($stored[0]);
    }

    /**
     * The point of the change: a bug is usually a sequence, and the
     * reporter no longer has to pick which frame of it to keep.
     */
    public function test_a_report_can_carry_several_screenshots_in_order(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(CreateBugReport::class)
            ->set('data.title', 'Three steps')
            ->set('data.description', 'Before, during, after.')
            ->set('data.screenshots', [
                UploadedFile::fake()->image('one.png'),
                UploadedFile::fake()->image('two.png'),
                UploadedFile::fake()->image('three.png'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $report = BugReport::where('title', 'Three steps')->firstOrFail();

        $this->assertCount(3, $report->screenshots);
        $this->assertCount(3, $report->screenshotUrls());

        foreach ($report->screenshots as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    /** Deleting a report has to take every image with it, not just the first. */
    public function test_deleting_a_report_removes_all_of_its_images(): void
    {
        Livewire::actingAs($this->makeMember())
            ->test(CreateBugReport::class)
            ->set('data.title', 'Goes away')
            ->set('data.description', 'With its evidence.')
            ->set('data.screenshots', [
                UploadedFile::fake()->image('a.png'),
                UploadedFile::fake()->image('b.png'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $report = BugReport::where('title', 'Goes away')->firstOrFail();
        $paths = $report->screenshots;

        $this->assertCount(2, $paths);

        $report->delete();

        foreach ($paths as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }
}
