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
        $quality = (int) ($data['q'] ?? 82);
        $dpr = (int) ($data['dpr'] ?? 1);
        $fit = $data['fit'] ?? 'cover';
        $width = isset($data['w']) ? (int) $data['w'] * $dpr : null;
        $height = isset($data['h']) ? (int) $data['h'] * $dpr : null;

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
