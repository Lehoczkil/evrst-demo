<?php

namespace Tests\Feature;

use App\Models\TeamMember;
use App\Models\TeamMemberGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamMemberGroupsPivotTest extends TestCase
{
    use RefreshDatabase;

    private TeamMember $member;
    private TeamMemberGroup $g1;
    private TeamMemberGroup $g2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = TeamMember::create([
            'name' => 'Pivot Probe',
        ]);
        $this->g1 = TeamMemberGroup::create([
            'slug' => 'group-one',
            'name' => ['en' => 'Group One', 'hu' => 'Csoport 1'],
            'kind' => 'department',
            'position' => 0,
        ]);
        $this->g2 = TeamMemberGroup::create([
            'slug' => 'group-two',
            'name' => ['en' => 'Group Two', 'hu' => 'Csoport 2'],
            'kind' => 'department',
            'position' => 1,
        ]);
    }

    public function test_set_primary_group_marks_exactly_one_row(): void
    {
        $this->member->groups()->sync([
            $this->g1->id => ['is_primary' => false],
            $this->g2->id => ['is_primary' => false],
        ]);

        $this->member->setPrimaryGroup($this->g2->id);

        $this->assertSame(1, $this->primaryCount());
        $this->assertSame($this->g2->id, $this->member->primaryGroup()->first()?->id);
    }

    public function test_switching_primary_demotes_previous_in_same_transaction(): void
    {
        $this->member->groups()->sync([
            $this->g1->id => ['is_primary' => false],
            $this->g2->id => ['is_primary' => false],
        ]);

        $this->member->setPrimaryGroup($this->g2->id);
        $this->assertSame($this->g2->id, $this->member->primaryGroup()->first()?->id);

        $this->member->setPrimaryGroup($this->g1->id);
        $this->assertSame(1, $this->primaryCount());
        $this->assertSame($this->g1->id, $this->member->primaryGroup()->first()?->id);
    }

    public function test_set_primary_group_null_clears_all_flags(): void
    {
        $this->member->groups()->sync([
            $this->g1->id => ['is_primary' => false],
            $this->g2->id => ['is_primary' => false],
        ]);
        $this->member->setPrimaryGroup($this->g1->id);
        $this->assertSame(1, $this->primaryCount());

        $this->member->setPrimaryGroup(null);

        $this->assertSame(0, $this->primaryCount());
    }

    public function test_current_groups_filters_out_ended_assignments(): void
    {
        $this->member->groups()->sync([
            $this->g1->id => ['is_primary' => false, 'ended_at' => null],
            $this->g2->id => ['is_primary' => false, 'ended_at' => now()->subDay()->toDateString()],
        ]);

        $current = $this->member->currentGroups()->pluck('team_member_groups.id')->all();
        $all = $this->member->groups()->pluck('team_member_groups.id')->all();

        $this->assertSame([$this->g1->id], $current);
        $this->assertEqualsCanonicalizing([$this->g1->id, $this->g2->id], $all);
    }

    private function primaryCount(): int
    {
        return $this->member->groups()
            ->newPivotStatement()
            ->where('team_member_id', $this->member->id)
            ->where('is_primary', true)
            ->count();
    }
}
