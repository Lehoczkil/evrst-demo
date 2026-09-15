<?php

namespace Tests\Feature;

use App\Models\MemberApplication;
use Database\Seeders\ApplicationFormSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The theme's load-bearing selectors must match the markup Filament emits.
 *
 * Three rules in admin-theme.css have now been found styling class names
 * this version of Filament does not render: `.fi-fo-component-ctn` (two
 * attempts at the mobile form bug went into it before anyone noticed it
 * matched nothing), `.fi-fo-field-wrp-label` (so every form label in the
 * panel was unstyled), and `.fi-fo-section-heading`.
 *
 * A stylesheet rule that matches nothing fails in perfect silence — no
 * error, no warning, just a page that looks untouched. A Filament upgrade
 * renaming one is invisible until someone photographs it. So the handful
 * the theme's typography actually depends on are asserted against a real
 * rendered page.
 *
 * This is deliberately a short, curated list. Most `fi-*` names in the
 * stylesheet are legitimately absent from any single page — modals render
 * lazily, empty states need an empty table, colour variants need a badge
 * of that colour. Asserting all of them would be noise.
 */
class AdminThemeSelectorsTest extends TestCase
{
    use RefreshDatabase;

    /** Selectors the panel's look would silently lose without. */
    private const LOAD_BEARING = [
        'fi-fo-field-label',            // form labels — the data voice
        'fi-section-header-heading',    // section titles
        'fi-section-header-description',
        'fi-section',                   // the glass surface itself
        'fi-input',                     // the value being read
        'fi-breadcrumbs',
        'fi-sidebar-item',
        'fi-header-heading',
    ];

    public function test_the_theme_styles_classes_that_actually_exist(): void
    {
        $this->seed([RoleSeeder::class, ApplicationFormSeeder::class]);
        $this->actingAs($this->makeAdmin());

        $application = MemberApplication::create([
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.test',
            'status' => MemberApplication::STATUS_PENDING,
            'answers' => ['why' => 'Egy elég hosszú válasz ahhoz, hogy tördelődjön.'],
        ]);

        $html = $this->get("/admin/member-applications/{$application->id}/edit")
            ->assertOk()
            ->getContent();

        $css = file_get_contents(base_path('public/css/admin-theme.css'));

        foreach (self::LOAD_BEARING as $class) {
            $this->assertStringContainsString(
                $class,
                $html,
                "Filament no longer renders .{$class} — the theme rule for it is now dead, silently.",
            );
            $this->assertMatchesRegularExpression(
                '/\.' . preg_quote($class, '/') . '\b/',
                $css,
                "the theme stopped styling .{$class}",
            );
        }
    }
}
