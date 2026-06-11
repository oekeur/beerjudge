<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompetitionJudgeAssignmentTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_member_can_register_as_judge_when_competition_is_open(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'open']);
        $member = Member::factory()->create();

        $this->actingAs($member)
            ->post(route('competitions.judges.store', $competition))
            ->assertRedirect();

        $this->assertDatabaseHas('competition_judge', [
            'competition_id' => $competition->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_member_can_register_as_judge_when_competition_is_judging(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $member = Member::factory()->create();

        $this->actingAs($member)
            ->post(route('competitions.judges.store', $competition))
            ->assertRedirect();

        $this->assertDatabaseHas('competition_judge', [
            'competition_id' => $competition->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_member_cannot_register_as_judge_when_competition_is_draft(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'draft']);
        $member = Member::factory()->create();

        $this->actingAs($member)
            ->post(route('competitions.judges.store', $competition))
            ->assertForbidden();

        $this->assertDatabaseMissing('competition_judge', [
            'competition_id' => $competition->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_member_cannot_register_as_judge_when_competition_is_closed(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'closed']);
        $member = Member::factory()->create();

        $this->actingAs($member)
            ->post(route('competitions.judges.store', $competition))
            ->assertForbidden();
    }

    public function test_member_can_leave_as_judge(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $member = Member::factory()->create();
        $competition->judges()->attach($member->id);

        $this->actingAs($member)
            ->delete(route('competitions.judges.destroy', $competition))
            ->assertRedirect();

        $this->assertDatabaseMissing('competition_judge', [
            'competition_id' => $competition->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_registering_as_judge_twice_does_not_duplicate_pivot_row(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'open']);
        $member = Member::factory()->create();

        $this->actingAs($member)->post(route('competitions.judges.store', $competition));
        $this->actingAs($member)->post(route('competitions.judges.store', $competition));

        $this->assertSame(1, $competition->judges()->where('member_id', $member->id)->count());
    }

    public function test_judging_screen_shows_register_prompt_when_not_a_judge(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $member = Member::factory()->create();

        $this->actingAs($member)
            ->get(route('judging.show', $competition))
            ->assertOk()
            ->assertSee(route('competitions.judges.store', $competition));
    }

    public function test_judging_screen_shows_leave_link_when_already_a_judge(): void
    {
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $member = Member::factory()->create();
        $competition->judges()->attach($member->id);

        $this->actingAs($member)
            ->get(route('judging.show', $competition))
            ->assertOk()
            ->assertSee(route('competitions.judges.destroy', $competition));
    }
}
