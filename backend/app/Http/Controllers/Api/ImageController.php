<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\AvifEncoder;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Encoders\PngEncoder;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Public on-the-fly image transform endpoint.
 *
 * Inputs are validated, resized via Intervention\Image v4 (GD by default,
 * Imagick if available), and cached on the public disk so subsequent hits
 * stream the file directly. SVGs and animated GIFs short-circuit to the
 * original bytes because resizing them is either meaningless (SVG is
 * already vector) or expensive (animated GIF would need per-frame work).
 */
class ImageController extends Controller
{
    private const CACHE_DIR = 'cache/img';

    private const ALLOWED_FORMATS = ['webp', 'avif', 'jpg', 'png', 'original'];

    private const ALLOWED_FITS = ['cover', 'contain', 'inside'];

    /**
     * The dimension ladder every request is snapped up to.
     *
     * Each distinct (path, w, h, f, q, fit) tuple writes a new file to the
     * app-data volume — the same volume the SQLite database and every
     * upload live on — and the endpoint is public. Accepting any integer
     * from 16 to 3000 meant ~9 million reachable variants per source
     * image, i.e. a stranger could fill the disk with `curl` in a loop and
     * take the site down with it.
     *
     * Snapping bounds that to 23 widths × 23 heights. It costs nothing
     * visually: an image is only ever served larger than asked, never
     * smaller, and the browser scales it down. The values the SPA asks
     * for today (72 and 200, and 216 = 72 at 3x) are on the ladder
     * exactly, so nothing is re-rendered at a different size than before.
     */
    private const SIZE_STEPS = [
        16, 24, 32, 48, 64, 72, 96, 128, 160, 200, 216, 240,
        320, 400, 480, 600, 640, 800, 960, 1200, 1600, 2000, 3000,
    ];

    public function transform(Request $request): BinaryFileResponse|Response|JsonResponse|StreamedResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1024'],
            'w' => ['nullable', 'integer', 'min:16', 'max:3000'],
            'h' => ['nullable', 'integer', 'min:16', 'max:3000'],
            'f' => ['nullable', 'string', 'in:' . implode(',', self::ALLOWED_FORMATS)],
            'q' => ['nullable', 'integer', 'min:1', 'max:100'],
            'dpr' => ['nullable', 'integer', 'min:1', 'max:3'],
            'fit' => ['nullable', 'string', 'in:' . implode(',', self::ALLOWED_FITS)],
        ]);

        $path = $this->safePath($data['path']);
        if ($path === null) {
            return response()->json(['message' => 'Invalid path'], 422);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $source = $disk->path($path);

        $format = $data['f'] ?? 'webp';
        // Quality rounds to the nearest 10 for the same reason the
        // dimensions snap — see SIZE_STEPS. 82 (the SPA's default) and
        // 70 land on 80 and 70; the difference is not visible.
        $quality = $this->snapQuality((int) ($data['q'] ?? 82));
        $dpr = (int) ($data['dpr'] ?? 1);
        $fit = $data['fit'] ?? 'cover';
        // dpr multiplies before snapping, so a 2x/3x variant lands on the
        // ladder too rather than opening a second axis of its own.
        $width = isset($data['w']) ? $this->snapSize((int) $data['w'] * $dpr) : null;
        $height = isset($data['h']) ? $this->snapSize((int) $data['h'] * $dpr) : null;

        if ($this->isPassthrough($source) || $format === 'original') {
            return $this->streamOriginal($source);
        }

        $params = [
            'w' => $width,
            'h' => $height,
            'f' => $format,
            'q' => $quality,
            'fit' => $fit,
        ];
        $hash = md5($path . '|' . serialize($params));
        $extension = $format === 'jpg' ? 'jpg' : $format;
        $cacheRelative = self::CACHE_DIR . '/' . substr($hash, 0, 2) . '/' . $hash . '.' . $extension;
        $cachePath = $disk->path($cacheRelative);

        if (! is_file($cachePath)) {
            // Checked before the try, not inside it: a memory exhaustion
            // here would be fatal and the catch below would never run.
            if ($this->tooLargeToDecode($source)) {
                Log::warning('image too large to transform, serving original', [
                    'path' => $path,
                    'pixels' => @getimagesize($source) ? (@getimagesize($source)[0] * @getimagesize($source)[1]) : null,
                ]);

                return $this->streamOriginal($source);
            }

            try {
                $this->renderToCache($source, $cachePath, $width, $height, $fit, $format, $quality);
            } catch (\Throwable $e) {
                Log::warning('image transform failed', [
                    'path' => $path,
                    'params' => $params,
                    'error' => $e->getMessage(),
                ]);
                return $this->streamOriginal($source);
            }
        }

        return $this->streamCached($cachePath, $this->mimeFor($format));
    }

    public function meta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:1024'],
        ]);

        $path = $this->safePath($data['path']);
        if ($path === null) {
            return response()->json(['message' => 'Invalid path'], 422);
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $source = $disk->path($path);
        $hash = md5($path);
        $cacheRelative = self::CACHE_DIR . '/' . substr($hash, 0, 2) . '/' . $hash . '.meta.json';
        $cachePath = $disk->path($cacheRelative);

        if (is_file($cachePath)) {
            $cached = json_decode((string) file_get_contents($cachePath), true);
            if (is_array($cached) && isset($cached['width'], $cached['height'])) {
                return response()->json($cached);
            }
        }

        // Same reasoning as the transform: the header gives the dimensions
        // for free, and the LQIP is the only part that needs a decode. A
        // placeholder is worth less than a working page.
        if ($this->tooLargeToDecode($source)) {
            $size = @getimagesize($source);
            $payload = [
                'width' => (int) ($size[0] ?? 0),
                'height' => (int) ($size[1] ?? 0),
                'lqip' => null,
            ];

            $this->ensureDirectory(dirname($cachePath));
            file_put_contents($cachePath, json_encode($payload));

            return response()->json($payload);
        }

        try {
            $payload = $this->buildMeta($source);
        } catch (\Throwable $e) {
            Log::warning('image meta failed', ['path' => $path, 'error' => $e->getMessage()]);
            return response()->json(['message' => 'Could not read image'], 500);
        }

        $this->ensureDirectory(dirname($cachePath));
        file_put_contents($cachePath, json_encode($payload));

        return response()->json($payload);
    }

    /** Round a requested dimension up to the next rung of SIZE_STEPS. */
    private function snapSize(int $value): int
    {
        foreach (self::SIZE_STEPS as $step) {
            if ($value <= $step) {
                return $step;
            }
        }

        return self::SIZE_STEPS[count(self::SIZE_STEPS) - 1];
    }

    private function snapQuality(int $value): int
    {
        return max(10, min(100, (int) round($value / 10) * 10));
    }

    private function safePath(string $path): ?string
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        // Disallow absolute paths and Windows drive letters; relative under public disk only.
        if (str_starts_with($path, '/') || preg_match('#^[a-zA-Z]:[\\\\/]#', $path)) {
            return null;
        }

        return $path;
    }

    private function isPassthrough(string $source): bool
    {
        $mime = @mime_content_type($source);
        if ($mime === 'image/svg+xml' || $mime === 'image/svg') {
            return true;
        }
        if ($mime === 'image/gif' && $this->isAnimatedGif($source)) {
            return true;
        }
        return false;
    }

    private function isAnimatedGif(string $source): bool
    {
        $fh = @fopen($source, 'rb');
        if ($fh === false) return false;
        $count = 0;
        $chunk = '';
        while (! feof($fh) && $count < 2) {
            $chunk .= fread($fh, 1024 * 100);
            $count = preg_match_all('#\x00\x21\xF9\x04#s', $chunk);
            if (strlen($chunk) > 1024 * 1024) break;
        }
        fclose($fh);
        return $count > 1;
    }

    private function renderToCache(
        string $source,
        string $cachePath,
        ?int $width,
        ?int $height,
        string $fit,
        string $format,
        int $quality,
    ): void {
        $manager = ImageManager::usingDriver(GdDriver::class);
        $image = $manager->decodePath($source);

        if ($width !== null || $height !== null) {
            $w = $width;
            $h = $height;
            switch ($fit) {
                case 'cover':
                    if ($w !== null && $h !== null) {
                        $image = $image->cover($w, $h);
                    } else {
                        $image = $image->scaleDown(width: $w, height: $h);
                    }
                    break;
                case 'contain':
                    $image = $image->contain($w ?? $image->width(), $h ?? $image->height());
                    break;
                case 'inside':
                default:
                    $image = $image->scaleDown(width: $w, height: $h);
                    break;
            }
        }

        $encoder = match ($format) {
            'avif' => new AvifEncoder(quality: $quality),
            'jpg' => new JpegEncoder(quality: $quality),
            'png' => new PngEncoder(),
            default => new WebpEncoder(quality: $quality),
        };

        $encoded = $image->encode($encoder);

        $this->ensureDirectory(dirname($cachePath));
        file_put_contents($cachePath, (string) $encoded);
    }

    private function buildMeta(string $source): array
    {
        $manager = ImageManager::usingDriver(GdDriver::class);
        $image = $manager->decodePath($source);
        $width = $image->width();
        $height = $image->height();

        // 24px-wide LQIP keeps the data URI tiny. Aspect-ratio-preserved
        // height is enough for a blurred placeholder; the SPA will
        // background-size:cover it under the real image.
        $lqipWidth = 24;
        $lqipHeight = max(1, (int) round($height * ($lqipWidth / max(1, $width))));
        $lqip = $image->scaleDown(width: $lqipWidth, height: $lqipHeight)
            ->encode(new WebpEncoder(quality: 30));

        return [
            'width' => $width,
            'height' => $height,
            'lqip' => 'data:image/webp;base64,' . base64_encode((string) $lqip),
        ];
    }

    /**
     * Would decoding this file blow the memory limit?
     *
     * GD expands an image to raw truecolour — about four bytes a pixel —
     * so a 503 KB PNG that happens to be 50 megapixels wants ~213 MB. And
     * a memory exhaustion is a FATAL error, not an exception: the
     * try/catch around the transform never runs, the worker dies, and the
     * caller gets a 500 instead of the fallback the catch was written to
     * provide. That is exactly how one sponsor logo turned into a broken
     * image on the public site.
     *
     * So the size is read from the file HEADER, which costs nothing, and
     * anything over budget is served as-is rather than decoded.
     */
    private function tooLargeToDecode(string $source): bool
    {
        $size = @getimagesize($source);
        if (! is_array($size) || ! isset($size[0], $size[1])) {
            // Unreadable header: let the decoder decide, it has a catch.
            return false;
        }

        $pixels = (int) $size[0] * (int) $size[1];

        return $pixels > self::maxDecodablePixels();
    }

    /**
     * Four bytes a pixel for the source, and the transform needs room for
     * an output canvas and the encoder on top — so budget a third of the
     * limit for the source bitmap. A missing or unlimited memory_limit
     * falls back to a figure that is safe on the smallest box we deploy to.
     */
    private static function maxDecodablePixels(): int
    {
        $limit = trim((string) ini_get('memory_limit'));

        if ($limit === '' || $limit === '-1') {
            return 24_000_000;
        }

        $unit = strtolower(substr($limit, -1));
        $bytes = (int) $limit;
        $bytes *= match ($unit) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return max(4_000_000, (int) ($bytes / 3 / 4));
    }

    private function streamOriginal(string $source): BinaryFileResponse
    {
        $mime = @mime_content_type($source) ?: 'application/octet-stream';
        $etag = '"' . md5_file($source) . '"';

        return response()->file($source, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }

    private function streamCached(string $cachePath, string $mime): BinaryFileResponse
    {
        $etag = '"' . md5_file($cachePath) . '"';

        return response()->file($cachePath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }

    private function mimeFor(string $format): string
    {
        return match ($format) {
            'avif' => 'image/avif',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            default => 'image/webp',
        };
    }

    private function ensureDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }
}
