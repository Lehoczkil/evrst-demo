<?php

namespace Tests\Feature;

use App\Filament\Resources\TeamMemberGroups\Pages\CreateTeamMemberGroup;
use App\Models\TeamMemberGroup;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sort order cannot go below zero.
 *
 * Every `position` column in the schema is an `unsignedInteger`, but the
 * inputs only said `->numeric()` — so the panel happily accepted -5. On
 * SQLite that stores without complaint and sorts the row ahead of
 * everything else; on MySQL or Postgres the same save is a 500. Either
 * way it is a value the column was never meant to hold.
 *
 * Tested on the positions form because that is where it was reported, but
 * the guard was applied to all eleven position inputs in the panel.
 */
class SortOrderInputTest extends TestCase
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

    /** @return array<string, mixed> */
    private static function group(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Avionics',
            'name_hu' => 'Avionika',
            'slug' => 'avionics',
            'kind' => 'department',
        ], $overrides);
    }

    public function test_a_negative_sort_order_is_rejected(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(CreateTeamMemberGroup::class)
            ->fillForm(self::group(['position' => -5]))
            ->call('create')
            ->assertHasFormErrors(['position']);

        $this->assertSame(0, TeamMemberGroup::count());
    }

    public function test_a_fractional_sort_order_is_rejected(): void
    {
        Livewire::actingAs($this->makeAdmin())
            ->test(CreateTeamMemberGroup::class)
            ->fillForm(self::group(['position' => 2.5]))
            ->call('create')
            ->assertHasFormErrors(['position']);

        $this->assertSame(0, TeamMemberGroup::count());
    }

    public function test_zero_and_above_are_accepted(): void
    {
        foreach ([0, 7] as $index => $position) {
            Livewire::actingAs($this->makeAdmin())
                ->test(CreateTeamMemberGroup::class)
                ->fillForm(self::group([
                    'slug' => "group-{$index}",
                    'position' => $position,
                ]))
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame([0, 7], TeamMemberGroup::orderBy('position')->pluck('position')->all());
    }
}
