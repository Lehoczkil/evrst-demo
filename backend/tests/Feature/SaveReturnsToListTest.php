<?php

namespace Tests\Feature;

use App\Auth\Perm;
use App\Filament\Resources\TeamMemberGroups\Pages\CreateTeamMemberGroup;
use App\Filament\Resources\TeamMemberGroups\Pages\EditTeamMemberGroup;
use App\Filament\Resources\TeamMemberGroups\TeamMemberGroupResource;
use App\Filament\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Resources\TeamMembers\TeamMemberResource;
use App\Models\Role;
use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Saving anything in the panel returns to that resource's table.
 *
 * The rule is set once on the panel (`resourceCreatePageRedirect` /
 * `resourceEditPageRedirect` in AdminPanelProvider), so this tests the
 * behaviour on a spread of resources rather than the setting: a page that
 * declared its own getRedirectUrl() would opt out silently, and the point
 * is that none of them do.
 */
class SaveReturnsToListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();

        $this->admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@evrst.test',
            'password' => Hash::make('secret-secret'),
            'role_id' => Role::where('key', Perm::ROLE_ADMIN)->value('id'),
            'password_changed_at' => now(),
        ]);

        $this->actingAs($this->admin);
    }

    public function test_editing_a_team_member_returns_to_the_table(): void
    {
        $member = TeamMember::create(['name' => 'Pencz Máté']);

        Livewire::test(EditTeamMember::class, ['record' => $member->getKey()])
            ->fillForm(['name' => 'Pencz Máté renamed'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(TeamMemberResource::getUrl('index'));
    }

    public function test_editing_a_group_returns_to_the_table(): void
    {
        $group = TeamMemberGroup::create([
            'slug' => 'elektronika',
            'name' => ['en' => 'Electronics', 'hu' => 'Elektronika'],
        ]);

        Livewire::test(EditTeamMemberGroup::class, ['record' => $group->getKey()])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(TeamMemberGroupResource::getUrl('index'));
    }

    public function test_creating_a_record_returns_to_the_table_not_its_edit_page(): void
    {
        Livewire::test(CreateTeamMemberGroup::class)
            ->fillForm([
                'name_en' => 'Avionics',
                'name_hu' => 'Avionika',
                'slug' => 'avionics',
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertRedirect(TeamMemberGroupResource::getUrl('index'));
    }
}
