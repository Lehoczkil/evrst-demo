<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 * Strip developer comments out of the Blade views, in place.
 *
 * Blade's own `{{-- --}}` never reaches the browser, but two kinds of
 * comment in these files do:
 *
 *   · HTML comments, served verbatim;
 *   · comments inside an inline `<script>` or `<style>`, which is just
 *     text as far as Blade is concerned.
 *
 * The panel leans on inline scripts for its render hooks, so a reader of
 * the page source got a guided tour of how the help modal, the sidebar
 * search injector and the desktop-view toggle work. That is information
 * disclosure, and it is dead weight on every page load.
 *
 * Meant to run against the *image*, not the repo: the Dockerfile calls it
 * after `COPY . .`, so the comments stay in git and never ship. Running it
 * locally would rewrite your working tree — hence --dry-run as the way to
 * inspect, and the explicit warning below.
 *
 * ## Why it only strips whole-line comments
 *
 * A correct JS comment stripper needs a tokeniser: `//` inside a string
 * (`'https://evrst.hu'`), inside a regex literal, or inside a template
 * literal is not a comment, and a regex that does not know the difference
 * will happily corrupt working code in a production image where nobody
 * is watching.
 *
 * So this only removes a comment that occupies a WHOLE LINE — the first
 * non-whitespace characters on the line are `//` or the line sits inside a
 * block comment that itself began on its own line. A URL in a string is
 * never the first thing on its line, so it cannot be touched. Trailing
 * comments after code survive; that is the price of not needing a parser,
 * and they are the minority.
 */
class StripViewComments extends Command
{
    protected $signature = 'views:strip-comments
                            {--dry-run : List what would change without writing}
                            {--path=resources/views : Directory to walk, relative to the app root}';

    protected $description = 'Remove HTML and inline-script comments from Blade views (build step; rewrites files in place)';

    public function handle(): int
    {
        $root = base_path($this->option('path'));

        if (! is_dir($root)) {
            $this->error("Not a directory: {$root}");

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $files = Finder::create()->files()->in($root)->name('*.blade.php');

        $changed = 0;
        $removed = 0;

        foreach ($files as $file) {
            $original = $file->getContents();
            $stripped = self::strip($original);

            if ($stripped === $original) {
                continue;
            }

            $changed++;
            $removed += substr_count($original, "\n") - substr_count($stripped, "\n");

            if (! $dry) {
                file_put_contents($file->getRealPath(), $stripped);
            }

            $this->line(($dry ? '  would strip  ' : '  stripped     ')
                . str_replace(base_path() . '/', '', $file->getRealPath()));
        }

        $this->info(sprintf(
            '%s %d file(s), %d line(s) of comment.',
            $dry ? 'Would strip' : 'Stripped',
            $changed,
            $removed,
        ));

        return self::SUCCESS;
    }

    /**
     * The transformation, exposed for testing.
     *
     * Order matters: HTML comments first (they can wrap anything), then
     * the contents of each inline script/style block.
     */
    public static function strip(string $source): string
    {
        $source = self::stripHtmlComments($source);

        return preg_replace_callback(
            '#(<(script|style)\b(?![^>]*\bsrc=)[^>]*>)(.*?)(</\2>)#is',
            fn (array $m) => $m[1] . self::stripWholeLineComments($m[3]) . $m[4],
            $source,
        ) ?? $source;
    }

    /**
     * Drop `<!-- … -->`, except a conditional comment (`<!--[if …`), which
     * is markup with meaning rather than prose.
     */
    private static function stripHtmlComments(string $source): string
    {
        return preg_replace('/^[ \t]*<!--(?!\[if )(?:(?!-->).)*?-->[ \t]*\R?/ms', '', $source)
            ?? $source;
    }

    /**
     * Remove comment lines from a script or style body.
     *
     * Line by line, so the decision is always "is this ENTIRE line a
     * comment" — never "is there a `//` somewhere in it".
     */
    private static function stripWholeLineComments(string $body): string
    {
        $out = [];
        $inBlock = false;

        foreach (preg_split('/\R/', $body) as $line) {
            $trimmed = ltrim($line);

            if ($inBlock) {
                // The line that closes the block is dropped with it, unless
                // code follows the `*/` on the same line.
                if (($end = strpos($trimmed, '*/')) !== false) {
                    $inBlock = false;
                    $rest = trim(substr($trimmed, $end + 2));

                    if ($rest !== '') {
                        $out[] = $rest;
                    }
                }

                continue;
            }

            if (str_starts_with($trimmed, '//')) {
                continue;
            }

            if (str_starts_with($trimmed, '/*')) {
                // A one-liner (`/* … */`) closes on the same line.
                if (($end = strpos($trimmed, '*/')) !== false) {
                    $rest = trim(substr($trimmed, $end + 2));

                    if ($rest !== '') {
                        $out[] = $rest;
                    }

                    continue;
                }

                $inBlock = true;

                continue;
            }

            $out[] = $line;
        }

        // Collapse the runs of blank lines a removed comment block leaves.
        return preg_replace("/\n{3,}/", "\n\n", implode("\n", $out)) ?? implode("\n", $out);
    }
}
