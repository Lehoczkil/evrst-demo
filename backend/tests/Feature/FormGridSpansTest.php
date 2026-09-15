<?php

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Form fields must fit the grid they are in at the narrowest breakpoint.
 *
 * Filament renders a schema grid as one column at the default breakpoint and
 * widens it further up (`--cols-default: repeat(1, …)`). A child declaring a
 * wider default span — what `columnSpan(['default' => 12, …])` emits — makes
 * the browser open eleven IMPLICIT columns to hold it. Implicit columns sit
 * outside the explicit grid, and `grid-column: 1 / -1` (which is exactly what
 * `columnSpanFull()` emits) means "to the end of the EXPLICIT grid" — so a
 * full-width field sharing a row with a `span 12` sibling collapses to a
 * twelfth of the width.
 *
 * That was the bug-report form on a phone: the title input and the
 * description textarea rendered about 90px wide, one or two characters per
 * line, while the fields that caused it filled the row.
 *
 * It was "fixed" twice in CSS against `.fi-fo-component-ctn` — a class this
 * version of Filament does not emit, so neither attempt ever matched an
 * element. Hence a test that reads the rendered HTML instead of trusting a
 * stylesheet.
 */
class FormGridSpansTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Notification::fake();
        Bus::fake();
    }

    public static function formPagesProvider(): array
    {
        return [
            'bug report' => ['/admin/bug-reports/create'],
            'task'       => ['/admin/tasks/create'],
            'team member'=> ['/admin/cms/team-members/create'],
            'user'       => ['/admin/users/create'],
            'event'      => ['/admin/cms/events/create'],
            'sponsor'    => ['/admin/cms/sponsors/create'],
            'mentor'     => ['/admin/cms/mentors/create'],
            'project'    => ['/admin/cms/about-projects/create'],
            'goal'       => ['/admin/cms/about-goals/create'],
            'contact'    => ['/admin/contacts/create'],
            'form field' => ['/admin/application-form/create'],
            'home texts' => ['/admin/home-content'],
        ];
    }

    #[DataProvider('formPagesProvider')]
    public function test_no_field_is_wider_than_the_grid_it_sits_in(string $url): void
    {
        $this->actingAs($this->makeAdmin());

        $html = $this->get($url)->assertOk()->getContent();

        // Every grid on these pages is one column at the default breakpoint.
        preg_match_all('/--cols-default:\s*repeat\((\d+)/', $html, $grids);
        $this->assertNotEmpty($grids[1], "no schema grid rendered on {$url}");
        $this->assertSame(
            ['1'],
            array_values(array_unique($grids[1])),
            "a grid on {$url} is not single-column by default — if that is deliberate, this test is where to say so",
        );

        // So no child may claim more than one column there.
        preg_match_all('/--col-span-default:\s*span\s*(\d+)/', $html, $spans);

        $tooWide = array_values(array_filter($spans[1], fn ($n) => (int) $n > 1));

        $this->assertSame([], $tooWide, sprintf(
            '%s declares --col-span-default: span %s in a single-column grid. '
            . 'Use \'default\' => 1; a wider default span opens implicit columns '
            . 'and squeezes every columnSpanFull() sibling to a fraction of the row.',
            $url,
            implode(', ', array_unique($tooWide)),
        ));
    }

    public function test_the_bug_report_form_specifically(): void
    {
        $this->actingAs($this->makeAdmin());

        $html = $this->get('/admin/bug-reports/create')->assertOk()->getContent();

        foreach (['title', 'description'] as $field) {
            $this->assertMatchesRegularExpression(
                '/schema-component::form\.' . $field . '".*?--col-span-default:\s*1 \/ -1/s',
                $html,
                "the {$field} field should span the whole row",
            );
        }

        foreach (['severity', 'page_url'] as $field) {
            $this->assertMatchesRegularExpression(
                '/schema-component::form\.' . $field . '".*?--col-span-default:\s*span 1/s',
                $html,
                "the {$field} field should take the single default column, not twelve",
            );
        }
    }
}
