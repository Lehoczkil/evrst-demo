<?php

namespace Tests\Unit;

use App\Console\Commands\StripViewComments;
use PHPUnit\Framework\TestCase;

/**
 * The view comment stripper must never corrupt code.
 *
 * It runs inside the Docker build, against the production image, with
 * nobody watching the output — so the bar is not "removes comments well",
 * it is "cannot break a working page". Every case below that keeps code
 * intact matters more than every case that removes a comment.
 */
class StripViewCommentsTest extends TestCase
{
    public function test_it_removes_html_comments(): void
    {
        $out = StripViewComments::strip("<div>\n    <!-- explain the div -->\n    <p>hi</p>\n</div>");

        $this->assertStringNotContainsString('explain the div', $out);
        $this->assertStringContainsString('<p>hi</p>', $out);
    }

    /** Conditional comments are markup, not prose. */
    public function test_it_keeps_conditional_comments(): void
    {
        $out = StripViewComments::strip("<!--[if BLOCK]><![endif]-->\n<p>hi</p>");

        $this->assertStringContainsString('<!--[if BLOCK]>', $out);
    }

    public function test_it_removes_whole_line_script_comments(): void
    {
        $out = StripViewComments::strip(<<<'HTML'
            <script>
                // Open the modal for a given help key.
                window.open = function (key) {
                    return key;
                };
            </script>
            HTML);

        $this->assertStringNotContainsString('Open the modal', $out);
        $this->assertStringContainsString('window.open = function (key)', $out);
        $this->assertStringContainsString('return key;', $out);
    }

    public function test_it_removes_whole_line_block_comments(): void
    {
        $out = StripViewComments::strip(<<<'HTML'
            <script>
                /*
                  Several lines of reasoning
                  nobody outside needs.
                */
                const a = 1;
            </script>
            HTML);

        $this->assertStringNotContainsString('reasoning', $out);
        $this->assertStringContainsString('const a = 1;', $out);
    }

    /**
     * The case that makes a naive stripper dangerous: `//` inside a string
     * is not a comment, and a regex that does not know the difference
     * silently truncates the line.
     */
    public function test_it_never_touches_a_url_in_a_string(): void
    {
        $code = <<<'HTML'
            <script>
                const a = 'https://evrst.hu/admin';
                const b = "http://example.test//double";
                const c = `https://${host}/x`;
            </script>
            HTML;

        $out = StripViewComments::strip($code);

        $this->assertStringContainsString("'https://evrst.hu/admin'", $out);
        $this->assertStringContainsString('"http://example.test//double"', $out);
        $this->assertStringContainsString('`https://${host}/x`', $out);
    }

    /** A trailing comment survives — removing it safely needs a parser. */
    public function test_it_leaves_trailing_comments_and_the_code_before_them(): void
    {
        $out = StripViewComments::strip("<script>\n    const a = 1; // why\n</script>");

        $this->assertStringContainsString('const a = 1;', $out);
    }

    /** An external script has no body of ours to strip. */
    public function test_it_ignores_script_tags_with_a_src(): void
    {
        $code = '<script src="/js/x.js"></script>';

        $this->assertSame($code, StripViewComments::strip($code));
    }

    public function test_it_strips_style_blocks_too(): void
    {
        $out = StripViewComments::strip("<style>\n    /* the brand ink */\n    body { color: red; }\n</style>");

        $this->assertStringNotContainsString('brand ink', $out);
        $this->assertStringContainsString('body { color: red; }', $out);
    }

    /** Blade directives and echoes inside a script must survive untouched. */
    public function test_it_preserves_blade_inside_a_script(): void
    {
        $code = <<<'HTML'
            <script>
                // a comment
                window.__evrstHelp = @json($pages);
                @if ($flag)
                    window.flag = true;
                @endif
            </script>
            HTML;

        $out = StripViewComments::strip($code);

        $this->assertStringNotContainsString('a comment', $out);
        $this->assertStringContainsString('@json($pages)', $out);
        $this->assertStringContainsString('@if ($flag)', $out);
        $this->assertStringContainsString('@endif', $out);
    }

    public function test_it_is_idempotent(): void
    {
        $code = "<script>\n    // gone\n    const a = 1;\n</script>";

        $once = StripViewComments::strip($code);

        $this->assertSame($once, StripViewComments::strip($once));
    }
}
