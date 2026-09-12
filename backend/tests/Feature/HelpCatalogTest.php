<?php

namespace Tests\Feature;

use Database\Seeders\CollectionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Every page in the panel has a help entry, in both languages.
 *
 * The "?" next to a page heading and the one injected into each sidebar
 * item are both driven by `admin.help.pages`, keyed by the route name with
 * the `filament.admin.` prefix stripped. A page with no entry silently
 * renders no button at all — which is how a dozen of them ended up with no
 * help while the feature looked finished.
 */
class HelpCatalogTest extends TestCase
{
    use RefreshDatabase;

    /** Auth screens other than the profile have no heading to hang a "?" on. */
    private const IGNORED = [
        'auth.login',
        'auth.logout',
        'auth.password-reset.request',
        'auth.password-reset.reset',
    ];

    /** @return list<string> */
    private function panelRouteKeys(): array
    {
        $keys = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || ! str_starts_with($name, 'filament.admin.')) {
                continue;
            }

            $key = substr($name, strlen('filament.admin.'));

            if (in_array($key, self::IGNORED, true)) {
                continue;
            }

            $keys[$key] = true;
        }

        $keys = array_keys($keys);
        sort($keys);

        return $keys;
    }

    public function test_every_panel_page_has_english_help(): void
    {
        $pages = (array) trans('admin.help.pages', locale: 'en');

        $missing = array_values(array_diff($this->panelRouteKeys(), array_keys($pages)));

        $this->assertSame([], $missing, 'Admin pages with no EN help entry: ' . implode(', ', $missing));
    }

    public function test_the_two_locales_carry_the_same_help_keys(): void
    {
        $en = array_keys((array) trans('admin.help.pages', locale: 'en'));
        $hu = array_keys((array) trans('admin.help.pages', locale: 'hu'));

        sort($en);
        sort($hu);

        $this->assertSame($en, $hu, 'EN and HU help catalogs have drifted apart.');
    }

    public function test_no_help_entry_points_at_a_route_that_no_longer_exists(): void
    {
        $pages = array_keys((array) trans('admin.help.pages', locale: 'en'));

        $orphans = array_values(array_diff($pages, $this->panelRouteKeys()));

        $this->assertSame([], $orphans, 'Help entries for dead routes: ' . implode(', ', $orphans));
    }

    public function test_every_help_entry_has_a_title_and_a_body(): void
    {
        foreach (['en', 'hu'] as $locale) {
            foreach ((array) trans('admin.help.pages', locale: $locale) as $key => $entry) {
                $this->assertIsArray($entry, "{$locale}/{$key} is not an array");
                $this->assertArrayHasKey('title', $entry, "{$locale}/{$key} has no title");
                $this->assertArrayHasKey('body', $entry, "{$locale}/{$key} has no body");
                $this->assertNotSame('', trim((string) $entry['title']), "{$locale}/{$key} title is empty");
                $this->assertNotSame('', trim((string) $entry['body']), "{$locale}/{$key} body is empty");
            }
        }
    }

    /**
     * The catalog having an entry isn't enough — the button has to reach
     * the page. Standard pages get it from PAGE_HEADER_HEADING_AFTER; the
     * profile page is a "simple" page and needs SIMPLE_PAGE_START instead.
     *
     * @dataProvider helpButtonPagesProvider
     */
    public function test_the_help_button_reaches_the_page(string $path, string $key): void
    {
        $this->seed([RoleSeeder::class, CollectionSeeder::class]);

        $html = $this->actingAs($this->makeAdmin())->get($path)->assertOk()->getContent();

        $this->assertStringContainsString(
            "window.evrstOpenHelp('{$key}')",
            $html,
            "{$path} renders no help button for {$key}.",
        );
    }

    public static function helpButtonPagesProvider(): array
    {
        return [
            'dashboard'      => ['/admin', 'pages.dashboard'],
            'profile'        => ['/admin/profile', 'auth.profile'],
            'items'          => ['/admin/items', 'resources.items.index'],
            'item-mgmt'      => ['/admin/item-management', 'pages.item-management'],
            'item-log'       => ['/admin/item-log', 'pages.item-log'],
            'contacts'       => ['/admin/contacts', 'resources.contacts.index'],
            'contact-groups' => ['/admin/contact-groups', 'resources.contact-groups.index'],
            'form'           => ['/admin/application-form', 'resources.application-form.index'],
            'form-sections'  => ['/admin/application-form-sections', 'resources.application-form-sections.index'],
            'bug-reports'    => ['/admin/bug-reports', 'resources.bug-reports.index'],
            'sponsor-create' => ['/admin/cms/sponsors/create', 'resources.cms.sponsors.create'],
        ];
    }

    /** Sign-in screens are "simple" pages too and must stay clean. */
    public function test_the_login_screen_grows_no_help_button(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();

        $this->assertStringNotContainsString("window.evrstOpenHelp('auth.login')", $html);
    }
}
