<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use App\Services\CompetitionRankingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JudgeParticipationGateTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $competition;

    private Style $style;

    protected function setUp(): void
    {
        parent::setUp();

        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $this->style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $this->competition->styles()->attach($this->style->id);
    }

    private function entryFor(Member $brewer): Entry
    {
        return Entry::create(['competition_id' => $this->competition->id, 'member_id' => $brewer->id, 'style_id' => $this->style->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serverData(Member $member): array
    {
        $html = $this->actingAs($member)->get(route('judging.show', $this->competition))->assertOk()->getContent();
        $this->assertSame(1, preg_match("/data-server='([^']*)'/", (string) $html, $m));

        return json_decode(html_entity_decode($m[1]), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Entry $entry, string $status = 'submitted'): array
    {
        return [
            'client_uuid' => fake()->uuid(),
            'entry_id' => $entry->id,
            'aroma' => 8, 'appearance' => 2, 'flavour' => 14, 'mouthfeel' => 4, 'overall' => 7,
            'status' => $status,
        ];
    }

    public function test_non_judge_gets_empty_entries_participate_prompt_and_no_entry_list(): void
    {
        $this->entryFor(Member::factory()->create(['name' => 'Brewer']));
        $member = Member::factory()->create();

        $data = $this->serverData($member);
        $this->assertSame([], $data['entries']);
        $this->assertSame([], $data['ownEntries']);
        $this->assertSame([], $data['progressEntries']);

        $this->actingAs($member)->get(route('judging.show', $this->competition))
            ->assertSee('data-testid="participate-prompt"', false)
            ->assertDontSee('<main', false)
            ->assertDontSee('Brewer');
    }

    public function test_admin_who_is_not_a_judge_still_sees_every_entry_in_the_progress_panel(): void
    {
        $brewer = Member::factory()->create(['name' => 'Brewer']);
        $admin = Member::factory()->admin()->create(['name' => 'Admin']);
        $first = $this->entryFor($brewer);
        $second = $this->entryFor($admin);

        $data = $this->serverData($admin);

        $this->assertSame([], $data['entries']);
        $this->assertSame([$first->id, $second->id], array_column($data['progressEntries'], 'id'));
        $this->assertSame([1, 2], array_column($data['progressEntries'], 'tasting_number'));
        $this->assertSame(['Brewer', 'Admin'], array_column($data['progressEntries'], 'brewer_name'));
    }

    public function test_non_admin_gets_no_progress_entries(): void
    {
        $this->entryFor(Member::factory()->create());
        $judge = Member::factory()->create();
        $this->competition->judges()->attach($judge->id);

        $this->assertSame([], $this->serverData($judge)['progressEntries']);
    }

    public function test_scoresheet_post_from_non_judge_is_forbidden_and_stores_nothing(): void
    {
        $entry = $this->entryFor(Member::factory()->create());
        $member = Member::factory()->create();

        $this->actingAs($member)->postJson('/scoresheets', $this->payload($entry))->assertForbidden();

        $this->assertSame(0, Scoresheet::count());
    }

    public function test_participating_unlocks_the_entry_list_and_saving(): void
    {
        $entry = $this->entryFor(Member::factory()->create());
        $member = Member::factory()->create();

        $this->actingAs($member)->post(route('competitions.judges.store', $this->competition))->assertRedirect();

        $data = $this->serverData($member);
        $this->assertSame([$entry->id], array_column($data['entries'], 'id'));
        $this->actingAs($member)->get(route('judging.show', $this->competition))->assertSee('<main', false);
        $this->actingAs($member)->postJson('/scoresheets', $this->payload($entry))->assertOk();
        $this->assertSame(1, Scoresheet::count());
    }

    public function test_register_query_parameter_unlocks_the_entry_list_on_the_same_load(): void
    {
        $this->entryFor(Member::factory()->create());
        $member = Member::factory()->create();

        $html = $this->actingAs($member)->get(route('judging.show', $this->competition).'?register=1')->getContent();

        $this->assertStringContainsString('<main', (string) $html);
        $this->assertTrue($this->competition->judges()->where('member_id', $member->id)->exists());
    }

    public function test_judge_who_leaves_after_submitting_keeps_counting_without_inflating_progress(): void
    {
        $brewer = Member::factory()->create();
        $entry = $this->entryFor($brewer);
        $leaver = Member::factory()->create(['name' => 'Leaver']);
        $stayer = Member::factory()->create(['name' => 'Stayer']);
        $admin = Member::factory()->admin()->create();
        $this->competition->judges()->attach([$leaver->id, $stayer->id]);

        $this->actingAs($leaver)->postJson('/scoresheets', $this->payload($entry))->assertOk();
        $this->actingAs($leaver)->delete(route('competitions.judges.destroy', $this->competition))->assertRedirect();

        // Ranking keeps the leaver's sheet.
        $ranked = app(CompetitionRankingService::class)->rank($this->competition);
        $this->assertSame(1, $ranked->first()['sheet_count']);

        $progress = $this->actingAs($admin)->getJson(route('judging.progress', $this->competition))->assertOk()->json();
        $this->assertSame(['submitted' => 1, 'total' => 2], $progress[$entry->id]);
    }
}
