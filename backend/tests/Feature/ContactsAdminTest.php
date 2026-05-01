<?php

namespace Tests\Feature;

use App\Filament\Resources\Contacts\Pages\CreateContact as CreateContactPage;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\ContactGroups\Pages\ListContactGroups;
use App\Models\Contact;
use App\Models\ContactGroup;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactsAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_admin_can_list_contacts_index(): void
    {
        $admin = $this->makeAdmin();
        $group = ContactGroup::create(['name' => 'Vendors']);
        Contact::create(['contact_group_id' => $group->id, 'name' => 'Acme Inc.']);

        $this->actingAs($admin)
            ->get('/admin/contacts')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/contact-groups')
            ->assertOk();
    }

    public function test_admin_can_create_contact_with_group(): void
    {
        $admin = $this->makeAdmin();
        $group = ContactGroup::create(['name' => 'Suppliers']);

        $this->actingAs($admin);

        Livewire::test(CreateContactPage::class)
            ->fillForm([
                'name' => 'Jane Vendor',
                'email' => 'jane@vendor.test',
                'phone' => '+36 1 234 5678',
                'contact_group_id' => $group->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', [
            'name' => 'Jane Vendor',
            'email' => 'jane@vendor.test',
            'contact_group_id' => $group->id,
        ]);
    }

    public function test_admin_can_delete_contact_via_table(): void
    {
        $admin = $this->makeAdmin();
        $contact = Contact::create(['name' => 'Goneby']);

        $this->actingAs($admin);

        Livewire::test(ListContacts::class)
            ->callTableAction('delete', $contact)
            ->assertHasNoTableActionErrors();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_member_can_view_contacts_but_not_create_edit_or_delete(): void
    {
        $member = $this->makeMember();
        $contact = Contact::create(['name' => 'Read Only']);

        $this->assertTrue($member->can(\App\Auth\Perm::CONTACTS_VIEW));
        $this->assertFalse($member->can(\App\Auth\Perm::CONTACTS_CREATE));
        $this->assertFalse($member->can(\App\Auth\Perm::CONTACTS_EDIT));
        $this->assertFalse($member->can(\App\Auth\Perm::CONTACTS_DELETE));

        $this->actingAs($member)
            ->get('/admin/contacts')
            ->assertOk();

        $resource = \App\Filament\Resources\Contacts\ContactResource::class;
        $this->assertTrue($resource::canViewAny());
        $this->assertFalse($resource::canCreate());
        $this->assertFalse($resource::canEdit($contact));
        $this->assertFalse($resource::canDelete($contact));
    }

    public function test_manager_can_create_and_edit_but_not_delete_contacts(): void
    {
        $manager = $this->makeManager();
        $contact = Contact::create(['name' => 'Manager Test Target']);

        $this->assertTrue($manager->can(\App\Auth\Perm::CONTACTS_VIEW));
        $this->assertTrue($manager->can(\App\Auth\Perm::CONTACTS_CREATE));
        $this->assertTrue($manager->can(\App\Auth\Perm::CONTACTS_EDIT));
        $this->assertFalse($manager->can(\App\Auth\Perm::CONTACTS_DELETE));

        $resource = \App\Filament\Resources\Contacts\ContactResource::class;
        $this->assertFalse($resource::canDelete($contact));

        $this->actingAs($manager);

        Livewire::test(CreateContactPage::class)
            ->fillForm(['name' => 'Manager Created'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contacts', ['name' => 'Manager Created']);
    }

    public function test_admin_can_create_contact_group(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\ContactGroups\Pages\CreateContactGroup::class)
            ->fillForm(['name' => 'New Group'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('contact_groups', ['name' => 'New Group']);
    }

    public function test_admin_can_list_contact_groups_via_livewire(): void
    {
        $admin = $this->makeAdmin();
        ContactGroup::create(['name' => 'List me']);
        $this->actingAs($admin);

        Livewire::test(ListContactGroups::class)
            ->assertSuccessful();
    }
}
