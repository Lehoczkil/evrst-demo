<?php

namespace Tests\Feature;

use App\Filament\Resources\ApplicationForm\Pages\CreateApplicationFormField;
use App\Filament\Resources\ApplicationForm\Pages\EditApplicationFormField;
use App\Models\ApplicationFormField;
use App\Models\ApplicationFormSection;
use App\Models\MemberApplication;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The join-us form is editable from the panel: a question added there is
 * asked, validated and stored with no code change, and the two questions
 * the accept flow is built on cannot be removed out from under it.
 *
 * (The seeded form itself comes from a migration, so it is present here.)
 */
class ApplicationFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Bus::fake();
    }

    private function contributionSection(): ApplicationFormSection
    {
        return ApplicationFormSection::where('key', 'contribution')->firstOrFail();
    }

    public function test_a_question_added_in_the_panel_is_served_and_stored(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(CreateApplicationFormField::class)
            ->fillForm([
                'section_id' => $this->contributionSection()->id,
                'type' => ApplicationFormField::TYPE_TEXT,
                'label_en' => 'Portfolio URL',
                'label_hu' => 'Portfólió link',
                'key' => 'portfolioUrl',
                'position' => 50,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $fields = collect($this->getJson('/api/application-form?lang=hu')->json('sections'))
            ->pluck('fields')
            ->flatten(1);
        $this->assertSame('Portfólió link', $fields->firstWhere('key', 'portfolioUrl')['label']);

        $this->postJson('/api/member-applications', $this->application(['portfolioUrl' => 'https://example.test']))
            ->assertCreated();

        $this->assertSame(
            'https://example.test',
            MemberApplication::latest('created_at')->firstOrFail()->answer('portfolioUrl'),
        );
    }

    public function test_a_new_choice_question_constrains_what_the_api_accepts(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(CreateApplicationFormField::class)
            ->fillForm([
                'section_id' => $this->contributionSection()->id,
                'type' => ApplicationFormField::TYPE_RADIO,
                'label_en' => 'Shift',
                'label_hu' => 'Műszak',
                'key' => 'shift',
                'options' => [
                    ['value' => 'weekday', 'label' => ['en' => 'Weekdays', 'hu' => 'Hétköznap']],
                    ['value' => 'weekend', 'label' => ['en' => 'Weekend', 'hu' => 'Hétvége']],
                ],
                'position' => 51,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->postJson('/api/member-applications', $this->application(['shift' => 'weekend']))
            ->assertCreated();

        $this->postJson('/api/member-applications', $this->application([
            'email' => 'other@example.test',
            'shift' => 'whenever',
        ]))->assertStatus(422)->assertJsonValidationErrors(['shift']);
    }

    public function test_a_system_question_keeps_its_key_type_and_visibility(): void
    {
        $name = ApplicationFormField::where('key', 'name')->firstOrFail();

        // The form disables these inputs; the payload is what actually has
        // to be refused, since a disabled input is only a UI affordance.
        Livewire::actingAs($this->makeAdmin())
            ->test(EditApplicationFormField::class, ['record' => $name->id])
            ->fillForm([
                'label_en' => 'Full name',
                'label_hu' => 'Teljes név',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $name->fresh();
        $this->assertSame('name', $fresh->key);
        $this->assertSame(ApplicationFormField::TYPE_TEXT, $fresh->type);
        $this->assertTrue($fresh->is_active);
        // The wording is theirs to change.
        $this->assertSame('Teljes név', $fresh->label['hu']);
    }

    public function test_a_system_question_cannot_be_deleted(): void
    {
        $email = ApplicationFormField::where('key', 'email')->firstOrFail();

        $this->actingAs($this->makeAdmin());

        $this->assertFalse(
            \App\Filament\Resources\ApplicationForm\ApplicationFormFieldResource::canDelete($email),
        );
        $this->assertTrue(
            \App\Filament\Resources\ApplicationForm\ApplicationFormFieldResource::canDelete(
                ApplicationFormField::where('key', 'skills')->firstOrFail(),
            ),
        );
    }

    public function test_an_answer_survives_the_question_being_deleted(): void
    {
        $this->postJson('/api/member-applications', $this->application())->assertCreated();

        ApplicationFormField::where('key', 'skills')->delete();

        $application = MemberApplication::latest('created_at')->firstOrFail();
        $rows = collect($application->answeredFields());
        $orphan = $rows->firstWhere('key', 'skills');

        $this->assertNotNull($orphan, 'an answer must not disappear with its question');
        $this->assertTrue($orphan['orphaned']);
    }

    public function test_reordering_changes_the_order_the_form_is_served_in(): void
    {
        ApplicationFormField::where('key', 'faculty')->update(['position' => 2]);
        ApplicationFormField::where('key', 'university')->update(['position' => 4]);

        $about = collect($this->getJson('/api/application-form')->json('sections'))
            ->firstWhere('key', 'about');

        $this->assertSame(
            ['email', 'name', 'faculty', 'education', 'university'],
            collect($about['fields'])->pluck('key')->all(),
        );
    }

    public function test_the_form_editor_is_closed_to_a_member(): void
    {
        $response = $this->actingAs($this->makeMember())->get('/admin/application-form');

        $this->assertContains($response->status(), [302, 403, 404]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function application(array $overrides = []): array
    {
        return array_merge([
            'email' => Str::random(8) . '@example.test',
            'name' => 'Applicant Person',
            'university' => 'Óbudai Egyetem',
            'faculty' => 'Bánki',
            'why' => 'I want to launch rockets.',
            'hours' => '5',
            'tasks' => 'Propulsion work.',
            'skills' => 'CAD, machining.',
        ], $overrides);
    }
}
