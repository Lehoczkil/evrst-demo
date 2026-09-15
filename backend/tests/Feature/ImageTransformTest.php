<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageTransformTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->putFixturePng('fixture.png', 400, 300);
    }

    public function test_transform_returns_webp_and_writes_cache(): void
    {
        $response = $this->get('/api/img?path=fixture.png&w=200&f=webp');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/webp');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);

        $this->assertCachedFileExists();
    }

    public function test_transform_serves_from_cache_on_second_call(): void
    {
        $this->get('/api/img?path=fixture.png&w=200&f=webp')->assertOk();

        $cachedFiles = $this->cachedFiles();
        $this->assertCount(1, $cachedFiles);
        $marker = filemtime($cachedFiles[0]);

        // Second call should hit the same on-disk cache file unchanged.
        $second = $this->get('/api/img?path=fixture.png&w=200&f=webp');
        $second->assertOk();

        $this->assertSame($marker, filemtime($cachedFiles[0]));
        $this->assertCount(1, $this->cachedFiles());
    }

    public function test_transform_supports_avif_and_jpg_outputs(): void
    {
        $this->get('/api/img?path=fixture.png&w=100&f=avif')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/avif');

        $this->get('/api/img?path=fixture.png&w=100&f=jpg&q=70')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_transform_blocks_path_traversal(): void
    {
        $this->getJson('/api/img?path=' . urlencode('../etc/passwd'))
            ->assertStatus(422);
    }

    public function test_transform_blocks_absolute_path(): void
    {
        $this->getJson('/api/img?path=' . urlencode('/etc/passwd'))
            ->assertStatus(422);
    }

    public function test_transform_returns_404_for_missing_file(): void
    {
        $this->getJson('/api/img?path=does-not-exist.png')
            ->assertStatus(404);
    }

    public function test_transform_validates_dimensions(): void
    {
        $this->getJson('/api/img?path=fixture.png&w=99999')
            ->assertStatus(422);

        $this->getJson('/api/img?path=fixture.png&w=2')
            ->assertStatus(422);
    }

    public function test_meta_returns_dimensions_and_lqip(): void
    {
        $response = $this->get('/api/img/meta?path=fixture.png');

        $response->assertOk()
            ->assertJsonStructure(['width', 'height', 'lqip']);

        $payload = $response->json();
        $this->assertSame(400, $payload['width']);
        $this->assertSame(300, $payload['height']);
        $this->assertStringStartsWith('data:image/webp;base64,', $payload['lqip']);
    }

    public function test_meta_caches_result_to_disk(): void
    {
        $this->get('/api/img/meta?path=fixture.png')->assertOk();

        $disk = Storage::disk('public');
        $hash = md5('fixture.png');
        $relative = 'cache/img/' . substr($hash, 0, 2) . '/' . $hash . '.meta.json';

        $this->assertTrue($disk->exists($relative));
    }

    public function test_meta_blocks_path_traversal(): void
    {
        $this->getJson('/api/img/meta?path=' . urlencode('../etc/passwd'))
            ->assertStatus(422);
    }

    private function putFixturePng(string $name, int $width, int $height): void
    {
        $im = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($im, 200, 50, 50);
        imagefill($im, 0, 0, $bg);
        $fg = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 10, 10, $width - 10, $height - 10, $fg);

        ob_start();
        imagepng($im);
        $bytes = ob_get_clean();
        imagedestroy($im);

        Storage::disk('public')->put($name, $bytes);
    }

    private function cachedFiles(): array
    {
        $root = Storage::disk('public')->path('cache/img');
        if (! is_dir($root)) return [];

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

    private function assertCachedFileExists(): void
    {
        $this->assertNotEmpty($this->cachedFiles(), 'Expected at least one cached image variant');
    }

    /**
     * A 503 KB PNG that happens to be 50 megapixels wants ~213 MB once GD
     * expands it, and blowing memory_limit is a FATAL error — the catch
     * around the transform never runs. So the size is checked from the
     * header first and an oversized file is streamed as-is.
     */
    public function test_an_image_too_large_to_decode_is_served_as_is(): void
    {
        // Small on disk, enormous in pixels: exactly the shape that killed
        // the sponsor logo. Built by hand so the test does not need a
        // 200 MB fixture.
        $path = 'sponsors/huge.png';
        Storage::disk('public')->put($path, $this->hugeButTinyPng());

        // 12000 x 12000 is 144 megapixels — over the budget at any
        // memory_limit worth deploying, so no need to squeeze the limit.
        $response = $this->get('/api/img?path=' . $path . '&w=200&f=webp');

        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('content-type'));

        $meta = $this->get('/api/img/meta?path=' . $path);
        $meta->assertOk();
        $meta->assertJsonPath('lqip', null);
        $this->assertGreaterThan(0, $meta->json('width'));
    }

    /**
     * A valid PNG header declaring huge dimensions with almost no data.
     * getimagesize() reads the IHDR and reports the declared size, which is
     * all the guard looks at.
     */
    private function hugeButTinyPng(): string
    {
        $ihdr = pack('NN', 12000, 12000) . pack('C5', 8, 6, 0, 0, 0);
        $chunk = fn (string $type, string $data) => pack('N', strlen($data)) . $type . $data
            . pack('N', crc32($type . $data));

        return "\x89PNG\r\n\x1a\n"
            . $chunk('IHDR', $ihdr)
            . $chunk('IDAT', gzcompress(str_repeat("\0", 64)))
            . $chunk('IEND', '');
    }
}
