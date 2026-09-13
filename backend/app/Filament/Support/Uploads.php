<?php

namespace App\Filament\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Shared upload hardening for every FileUpload in the panel.
 *
 * Two separate holes, and both need closing on every field:
 *
 * 1. `FileUpload::image()` only sets `mimetypes:image/*`, and
 *    `image/svg+xml` satisfies it. An SVG is a document — it can carry
 *    `<script>` — so a "profile photo" was a stored-XSS payload on our
 *    own origin, where it reaches the session cookie and the panel.
 *    Hence explicit raster allow-lists below instead of `->image()`.
 *
 * 2. Filament names the stored file `{ulid}.{client extension}` — the
 *    extension comes from the browser, not from the bytes. The web
 *    server picks the Content-Type off that extension, so
 *    `rocket.png.html` (or anything else the MIME sniff let through)
 *    would still be served as markup. `storedName()` re-derives the
 *    extension from the file's actual content and refuses the handful
 *    that browsers execute.
 */
final class Uploads
{
    /** Raster formats a browser paints but cannot execute. No SVG. */
    public const IMAGE_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/avif',
    ];

    /**
     * The above plus what iOS Safari and some Android browsers hand over
     * straight from the camera roll — see the note on SponsorForm.
     */
    public const PHONE_IMAGE_TYPES = [
        ...self::IMAGE_TYPES,
        'image/heic',
        'image/heif',
    ];

    /**
     * Extensions the browser will parse as an active document rather
     * than download or render inertly. Anything guessed into this list
     * is stored as `.bin` instead — the file is still retrievable, it
     * just cannot run.
     */
    private const EXECUTABLE_EXTENSIONS = [
        'svg', 'svgz', 'xml', 'xsl', 'xslt',
        'html', 'htm', 'xhtml', 'xht', 'shtml',
        'js', 'mjs', 'cjs',
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'php8', 'phps', 'pht', 'phar',
        'swf', 'xhtm',
    ];

    /**
     * Filename to store an upload under: a fresh ULID plus an extension
     * derived from the bytes (`guessExtension()` sniffs the content —
     * `getClientOriginalExtension()`, which Filament uses by default,
     * is whatever the request claimed).
     *
     * Pass this to `->getUploadedFileNameForStorageUsing(...)`.
     */
    public static function storedName(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        // Unknown bytes and active document types both end up inert.
        if ($extension === '' || in_array($extension, self::EXECUTABLE_EXTENSIONS, true)) {
            $extension = 'bin';
        }

        return Str::ulid() . '.' . $extension;
    }
}
