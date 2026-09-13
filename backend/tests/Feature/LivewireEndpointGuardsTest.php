<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;

use App\Filament\Pages\AboutContent;
use App\Filament\Pages\DatabaseInspector;
use App\Filament\Pages\HomeContent;
use App\Filament\Resources\OnshapeModels\Pages\ListOnshapeModels as OnshapeList;
use App\Filament\Resources\Tasks\Pages\KanbanBoard;
use App\Jobs\ExportOnshapeModelToGlb;
use App\Models\OnshapeModel;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * `/livewire/update` is a plain POST to an already-mounted component: it
 * never re-enters `mount()`, and it does not care what the Blade
 * rendered. Everything here is a gate that only existed on the way in.
 *
 * Covers:
 *  - M2 the Onshape export + connection test, which any Member could
 *    reach (they can view the resource) and which held the web request
 *    open for up to three minutes polling Onshape;
 *  - M4 the kanban reorder, which validated status changes per record but
 *    wrote `position` on every card in the payload;
 *  - L1 the singleton content pages and the schema browser, whose only
 *    guard was in `mount()`;
 *  - L2 the locale switch, which redirected to a caller-supplied URL.
 */
class LivewireEndpointGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Notification::fake();
        Mail::fake();
        Bus::fake();
        Http::fake();
    }

    /* ---------------------------------------------------------------- M2 */

    /**
     * Without keys the actions bail out before they do anything, which
     * would make the denial tests below pass for the wrong reason.
     */
    private function configureOnshape(): void
    {
        config(['services.onshape.access_key' => 'k', 'services.onshape.secret_key' => 's']);
    }

    private function onshapeModel(): OnshapeModel
    {
        return OnshapeModel::create([
            'user_id' => $this->makeAdmin()->id,
            'title' => 'Airframe',
            'document_id' => 'd' . str_repeat('0', 23),
            'workspace_id' => 'w' . str_repeat('0', 23),
            'element_id' => 'e' . str_repeat('0', 23),
        ]);
    }

    public function test_a_member_cannot_trigger_an_onshape_export(): void
    {
        $this->configureOnshape();
        $model = $this->onshapeModel();

        Livewire::actingAs($this->makeMember())
            ->test(OnshapeList::class)
            ->mountAction(TestAction::make('export_glb')->table($model))
            ->callMountedAction();

        Bus::assertNotDispatched(ExportOnshapeModelToGlb::class);
        $this->assertNull($model->fresh()->glb_status);
    }

    public function test_a_member_cannot_ping_onshape(): void
    {
        $this->configureOnshape();

        Livewire::actingAs($this->makeMember())
            ->test(OnshapeList::class)
            ->mountAction(TestAction::make('test_connection'))
            ->callMountedAction();

        Http::assertNothingSent();
    }

    /** Control: someone with models.edit still gets the export, on the queue. */
    public function test_an_admin_still_exports_and_it_goes_to_the_queue(): void
    {
        $this->configureOnshape();
        $model = $this->onshapeModel();

        Livewire::actingAs($this->makeAdmin())
            ->test(OnshapeList::class)
            ->mountAction(TestAction::make('export_glb')->table($model))
            ->callMountedAction();

        Bus::assertDispatched(ExportOnshapeModelToGlb::class);
        $this->assertSame(OnshapeModel::GLB_QUEUED, $model->fresh()->glb_status);
    }

    /* ---------------------------------------------------------------- M4 */

    private function task(string $title, ?User $assignee, int $position): Task
    {
        $task = Task::create([
            'title' => $title,
            'description' => 'Long enough to satisfy the minimum length rule.',
            'status' => Task::STATUS_TODO,
            'priority' => Task::PRIORITY_NORMAL,
            'supervisor_id' => $this->makeMember(['name' => "Sup {$title}"])->id,
            'due_date' => now()->addWeek()->toDateString(),
            'position' => $position,
        ]);

        if ($assignee) {
            $task->assignees()->sync([$assignee->id]);
        }

        return $task;
    }

    public function test_the_kanban_does_not_reorder_cards_the_member_is_not_on(): void
    {
        $member = $this->makeMember(['name' => 'Dragger']);
        $mine = $this->task('Mine', $member, 0);
        $theirs = $this->task('Theirs', null, 1);

        Livewire::actingAs($member)
            ->test(KanbanBoard::class)
            ->call('reorder', [Task::STATUS_TODO => [$theirs->id, $mine->id]]);

        // The member's own card takes the slot they dropped it into…
        $this->assertSame(1, $mine->fresh()->position);
        // …and the one they have no claim to keeps the position it had.
        $this->assertSame(1, $theirs->fresh()->position);
    }

    /** Control: tasks.edit reorders the whole board, as it always did. */
    public function test_an_admin_still_reorders_the_whole_board(): void
    {
        $first = $this->task('First', null, 0);
        $second = $this->task('Second', null, 1);

        Livewire::actingAs($this->makeAdmin())
            ->test(KanbanBoard::class)
            ->call('reorder', [Task::STATUS_TODO => [$second->id, $first->id]]);

        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
    }

    /* ---------------------------------------------------------------- L1 */

    /**
     * Mount as someone allowed in — that is the only way to get a valid
     * component snapshot — then make the call as somebody who is not.
     * That is the shape of the hole: the guard lived in mount(), and
     * mount() is not on the path of a /livewire/update POST.
     */
    private function callAsMember(string $page, string $method, array $args = []): void
    {
        $component = Livewire::actingAs($this->makeAdmin())->test($page);

        $this->actingAs($this->makeMember());

        $component->call($method, ...$args)->assertForbidden();
    }

    public function test_a_member_cannot_save_the_about_content_page(): void
    {
        $this->callAsMember(AboutContent::class, 'save');
    }

    public function test_a_member_cannot_save_the_home_content_page(): void
    {
        $this->callAsMember(HomeContent::class, 'save');
    }

    public function test_a_member_cannot_browse_the_schema(): void
    {
        $this->callAsMember(DatabaseInspector::class, 'selectTable', ['users']);
    }

    /* ---------------------------------------------------------------- L2 */

    public static function offsiteTargets(): array
    {
        return [
            'absolute'          => ['https://evil.test/phish'],
            'protocol relative' => ['//evil.test/phish'],
            'backslash'         => ['/\\evil.test/phish'],
            'javascript'        => ['javascript:alert(1)'],
            'header injection'  => ["/admin\r\nLocation: https://evil.test"],
        ];
    }

    #[DataProvider('offsiteTargets')]
    public function test_the_locale_switch_refuses_an_offsite_return_target(string $target): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/locale', ['locale' => 'hu', 'redirect' => $target])
            ->assertRedirect('/admin');
    }

    #[DataProvider('offsiteTargets')]
    public function test_an_offsite_referer_is_ignored_too(string $target): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/locale', ['locale' => 'hu'], ['referer' => $target])
            ->assertRedirect('/admin');
    }

    public function test_a_local_return_target_still_works(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/locale', ['locale' => 'hu', 'redirect' => '/admin/tasks?status=TODO'])
            ->assertRedirect('/admin/tasks?status=TODO');
    }

    /** A referer is an absolute URL of our own page — keep its path. */
    public function test_our_own_referer_is_followed(): void
    {
        $this->actingAs($this->makeAdmin())
            ->post('/admin/locale', ['locale' => 'hu'], ['referer' => url('/admin/cms/team-members')])
            ->assertRedirect('/admin/cms/team-members');
    }
}
