<?php

namespace Tests\Feature;

use App\Models\MemberApplication;
use Database\Seeders\ApplicationFormSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The submitted answers have to be readable as question/answer pairs.
 *
 * They rendered as one undifferentiated block because the markup carried
 * Tailwind utilities composed at runtime in PHP — `text-xs uppercase
 * tracking-wide` on the <dt>. Tailwind's scanner only sees classes present
 * in source at build time, so none of them exist in the compiled CSS
 * (`uppercase` appears in it zero times) and the question was simply
 * unstyled. Twenty-four lines of identical grey.
 *
 * So the markup uses our own class names, styled in a stylesheet we
 * control, and both halves of that are asserted here.
 */
class ApplicationAnswersTest extends TestCase
{
    use RefreshDatabase;

    public function test_questions_and_answers_are_marked_up_and_styled_distinctly(): void
    {
        $this->seed([RoleSeeder::class, ApplicationFormSeeder::class]);
        $this->actingAs($this->makeAdmin());

        $application = MemberApplication::create([
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.test',
            'status' => MemberApplication::STATUS_PENDING,
            'answers' => ['why' => 'Mert érdekel a rakétázás.'],
        ]);

        $html = $this->get("/admin/member-applications/{$application->id}/edit")
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('evrst-answers', $html);
        $this->assertStringContainsString('evrst-answers-row', $html);
        $this->assertStringContainsString('Mert érdekel a rakétázás.', $html);

        $css = file_get_contents(base_path('public/css/admin-theme.css'));

        foreach (['.evrst-answers-row dt', '.evrst-answers-row dd'] as $rule) {
            $this->assertStringContainsString($rule, $css, "{$rule} is not styled");
        }
    }

    public function test_it_does_not_reach_for_tailwind_utilities_built_at_runtime(): void
    {
        $source = file_get_contents(base_path(
            'app/Filament/Resources/MemberApplications/Schemas/MemberApplicationForm.php',
        ));

        // Only what is emitted as a class attribute — prose about the bug
        // is allowed to name the utilities that caused it.
        preg_match_all('/class="([^"]*)"/', $source, $matches);
        $emitted = implode(' ', $matches[1]);

        $this->assertNotSame('', $emitted, 'no class attribute found — did the renderer move?');

        foreach (['uppercase', 'tracking-', 'text-gray-', 'text-xs', 'text-sm', 'whitespace-'] as $utility) {
            $this->assertStringNotContainsString($utility, $emitted, sprintf(
                'A Tailwind utility (%s) composed here at runtime is invisible to the scanner '
                . 'and will not exist in the compiled CSS. Style it in admin-theme.css instead.',
                $utility,
            ));
        }
    }
}
