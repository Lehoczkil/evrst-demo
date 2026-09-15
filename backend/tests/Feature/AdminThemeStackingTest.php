<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Overlays must outrank every in-page stacking trick in admin-theme.css.
 *
 * The theme lifts a `.fi-section` to z-index 50 while it holds a focused
 * input, so a date picker can escape the stacking context the section's
 * backdrop-filter creates. The delete-confirmation modal sat at 40 — so on
 * any form with a focused field, which is every form someone is actually
 * using, the section painted over the open modal AND its backdrop-filter
 * blurred it. What was left was a sliver of the modal's top edge and a
 * close button; the confirm button was unreachable, and a task could not
 * be deleted.
 *
 * A number in one part of a stylesheet quietly outranking a number in
 * another is not something review catches, so it is asserted instead.
 */
class AdminThemeStackingTest extends TestCase
{
    private const THEME = 'public/css/admin-theme.css';

    public function test_every_overlay_outranks_every_page_level_z_index(): void
    {
        $css = file_get_contents(base_path(self::THEME));
        $this->assertNotEmpty($css, 'the theme stylesheet is missing');

        // The ladder the overlays are declared on.
        preg_match_all('/--evrst-z-[a-z-]+:\s*(\d+)/', $css, $ladder);
        $this->assertNotEmpty($ladder[1], 'the overlay ladder is gone from :root');
        $lowestOverlay = min(array_map('intval', $ladder[1]));

        // Every literal z-index elsewhere in the file.
        preg_match_all('/z-index:\s*(\d+)/', $css, $literals);
        $pageLevel = array_map('intval', $literals[1]);

        $this->assertNotEmpty($pageLevel, 'no literal z-index found — did the file move?');

        $offenders = array_values(array_filter($pageLevel, fn ($n) => $n >= $lowestOverlay));

        $this->assertSame([], $offenders, sprintf(
            'z-index %s is at or above the overlay ladder (%d). An overlay must win against '
            . 'in-page stacking, or a modal opens behind the form that spawned it.',
            implode(', ', array_unique($offenders)),
            $lowestOverlay,
        ));
    }

    public function test_the_modal_and_the_editor_use_the_ladder_not_a_number(): void
    {
        $css = file_get_contents(base_path(self::THEME));

        foreach (['fi-modal-close-overlay', 'fi-modal-window-ctn', 'fi-fo-file-upload-editor'] as $selector) {
            $this->assertMatchesRegularExpression(
                '/\.' . preg_quote($selector, '/') . '\b[^{]*\{[^}]*z-index:\s*var\(--evrst-z-/s',
                $css,
                "{$selector} should take its z-index from the ladder, not a literal",
            );
        }
    }
}
