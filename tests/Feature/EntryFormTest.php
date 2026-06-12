<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EntryFormTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeOpenCompetition(): Competition
    {
        $comp = Competition::create(['name' => 'Spring Cup', 'year' => 2025, 'status' => Competition::STATUS_OPEN]);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American Beer', 'name' => 'Light Lager']);
        $comp->styles()->attach($style->id);

        return $comp->fresh();
    }

    public function test_member_can_view_entry_form_for_open_competition(): void
    {
        $member = Member::factory()->create();
        $comp = $this->makeOpenCompetition();

        $this->actingAs($member)
            ->get(route('competitions.entries.create', $comp))
            ->assertOk();
    }

    public function test_member_can_submit_entry(): void
    {
        $member = Member::factory()->create();
        $comp = $this->makeOpenCompetition();
        $style = $comp->styles->first();

        $this->actingAs($member)
            ->post(route('competitions.entries.store', $comp), [
                'style_id' => $style->id,
                'brewer_notes' => 'Dry hopped with Citra.',
            ])
            ->assertRedirect(route('competitions.entries.create', $comp));

        $this->assertDatabaseHas('entries', [
            'competition_id' => $comp->id,
            'member_id' => $member->id,
            'style_id' => $style->id,
        ]);
    }

    public function test_entry_is_attributed_to_authenticated_member(): void
    {
        $member = Member::factory()->create();
        $other = Member::factory()->create();
        $comp = $this->makeOpenCompetition();
        $style = $comp->styles->first();

        $this->actingAs($member)
            ->post(route('competitions.entries.store', $comp), [
                'style_id' => $style->id,
            ]);

        $entry = Entry::where('competition_id', $comp->id)->first();
        $this->assertEquals($member->id, $entry->member_id);
        $this->assertNotEquals($other->id, $entry->member_id);
    }

    public function test_bottle_number_is_null_after_member_submission(): void
    {
        $member = Member::factory()->create();
        $comp = $this->makeOpenCompetition();
        $style = $comp->styles->first();

        $this->actingAs($member)
            ->post(route('competitions.entries.store', $comp), ['style_id' => $style->id]);

        $this->assertNull(Entry::where('competition_id', $comp->id)->value('bottle_number'));
    }

    public function test_entry_form_rejects_draft_competition(): void
    {
        $member = Member::factory()->create();
        $comp = Competition::create(['name' => 'Draft Cup', 'year' => 2025, 'status' => Competition::STATUS_DRAFT]);

        $this->actingAs($member)
            ->get(route('competitions.entries.create', $comp))
            ->assertForbidden();
    }

    public function test_entry_form_rejects_judging_competition(): void
    {
        $member = Member::factory()->create();
        $comp = Competition::create(['name' => 'Judging Cup', 'year' => 2025, 'status' => Competition::STATUS_JUDGING]);

        $this->actingAs($member)
            ->get(route('competitions.entries.create', $comp))
            ->assertForbidden();
    }

    public function test_entry_form_rejects_closed_competition(): void
    {
        $member = Member::factory()->create();
        $comp = Competition::create(['name' => 'Closed Cup', 'year' => 2025, 'status' => Competition::STATUS_CLOSED]);

        $this->actingAs($member)
            ->get(route('competitions.entries.create', $comp))
            ->assertForbidden();
    }

    public function test_duplicate_style_entry_rejected(): void
    {
        $member = Member::factory()->create();
        $comp = $this->makeOpenCompetition();
        $style = $comp->styles->first();

        Entry::create([
            'competition_id' => $comp->id,
            'member_id' => $member->id,
            'style_id' => $style->id,
        ]);

        $this->actingAs($member)
            ->post(route('competitions.entries.store', $comp), ['style_id' => $style->id])
            ->assertSessionHasErrors(['style_id']);
    }

    public function test_style_not_in_competition_rejected(): void
    {
        $member = Member::factory()->create();
        $comp = $this->makeOpenCompetition();
        $otherStyle = Style::create(['code' => '2A', 'category_name' => 'International Lager', 'name' => 'International Pale Lager']);

        $this->actingAs($member)
            ->post(route('competitions.entries.store', $comp), ['style_id' => $otherStyle->id])
            ->assertSessionHasErrors(['style_id']);
    }
}
