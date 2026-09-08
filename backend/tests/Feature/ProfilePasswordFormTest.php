<?php

namespace Tests\Feature;

use App\Filament\Auth\ForceChangeProfile;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
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
}
