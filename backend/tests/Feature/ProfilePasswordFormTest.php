<?php

namespace Tests\Feature;

use App\Filament\Auth\ForceChangeProfile;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Filament\Livewire\GlobalSearch;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Livewire\Mechanisms\ComponentRegistry;
use Tests\TestCase;

/**
 * The profile page is the first thing a freshly-onboarded member sees —
 * RequirePasswordChange bounces them there until they rotate the temp
 * password from the welcome mail. Filament's stock EditProfile hides the
 * confirmation + current-password inputs until `password` is filled, and
 * the reveal rides on a 500 ms live debounce, so the form looked like it
 * only wanted one value and rejected the submit. These tests pin all
 * three inputs visible up front, and pin that they stay optional for
 * someone who is only editing their name.
 */
class ProfilePasswordFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Filament::setCurrentPanel('admin');
    }

    public function test_all_three_password_inputs_render_before_anything_is_typed(): void
    {
        $user = $this->makeMember(['password_changed_at' => null]);

        Livewire::actingAs($user)
            ->test(ForceChangeProfile::class)
            ->assertFormFieldVisible('password')
            ->assertFormFieldVisible('passwordConfirmation')
            ->assertFormFieldVisible('currentPassword');
    }

    public function test_first_login_password_change_succeeds_in_one_submit(): void
    {
        $user = $this->makeMember([
            'password' => 'temp-pass-1234',
            'password_changed_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ForceChangeProfile::class)
            ->fillForm([
                'password' => 'Str0ng-New-Pass!',
                'passwordConfirmation' => 'Str0ng-New-Pass!',
                'currentPassword' => 'temp-pass-1234',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertNotNull($user->password_changed_at);
        $this->assertTrue(Hash::check('Str0ng-New-Pass!', $user->password));
    }

    public function test_editing_only_the_name_does_not_require_the_password_fields(): void
    {
        $user = $this->makeMember(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test(ForceChangeProfile::class)
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('New Name', $user->refresh()->name);
    }

    public function test_a_new_password_without_its_confirmation_is_rejected(): void
    {
        $user = $this->makeMember([
            'password' => 'temp-pass-1234',
            'password_changed_at' => null,
        ]);

        Livewire::actingAs($user)
            ->test(ForceChangeProfile::class)
            ->fillForm([
                'password' => 'Str0ng-New-Pass!',
                'currentPassword' => 'temp-pass-1234',
            ])
            ->call('save')
            ->assertHasFormErrors(['passwordConfirmation']);

        $this->assertNull($user->refresh()->password_changed_at);
    }

    /**
     * RequirePasswordChange has to let the profile form's own Livewire
     * traffic through, but it used to let through *everything* under
     * livewire/ — including the global search component in the topbar, so
     * a user who had not set a password yet could list records from it.
     */
    public function test_the_gate_lets_the_profile_component_through(): void
    {
        $user = $this->makeMember(['password_changed_at' => null]);

        // CSRF runs ahead of this gate and would answer 419 either way.
        $response = $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user)
            ->post('/livewire/update', [
            'components' => [[
                'snapshot' => json_encode([
                    'data' => [],
                    'memo' => ['id' => 'x', 'name' => app(ComponentRegistry::class)->getName(ForceChangeProfile::class)],
                    'checksum' => 'whatever',
                ]),
                'updates' => [],
                'calls' => [],
            ]],
        ]);

        // It gets as far as Livewire (which then rejects the fake
        // checksum) — what matters is that the middleware did not bounce.
        $this->assertFalse($response->isRedirect(), 'the profile form must never be locked out of its own page');
    }

    public function test_the_gate_blocks_the_global_search_component(): void
    {
        $user = $this->makeMember(['password_changed_at' => null]);

        $this->withoutMiddleware(ValidateCsrfToken::class)
            ->actingAs($user)
            ->post('/livewire/update', [
            'components' => [[
                'snapshot' => json_encode([
                    'data' => [],
                    'memo' => ['id' => 'x', 'name' => app(ComponentRegistry::class)->getName(GlobalSearch::class)],
                    'checksum' => 'whatever',
                ]),
                'updates' => [],
                'calls' => [],
            ]],
        ])->assertRedirect(Filament::getProfileUrl());
    }

    public function test_an_activated_user_is_not_gated(): void
    {
        $user = $this->makeMember();

        $this->actingAs($user)->get('/admin')->assertOk();
    }
}
