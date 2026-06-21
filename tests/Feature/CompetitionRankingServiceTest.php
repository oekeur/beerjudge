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

class CompetitionRankingServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Style $style;

    private Competition $competition;

    private Member $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $this->competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'closed']);
        $this->competition->styles()->attach($this->style->id);
        $this->judge = Member::factory()->create(['email' => 'judge@t.com']);
    }

    private function makeEntry(string $email): Entry
    {
        $member = Member::factory()->create(['email' => $email]);

        return Entry::create([
            'competition_id' => $this->competition->id,
            'member_id' => $member->id,
            'style_id' => $this->style->id,
            'bottle_number' => fake()->unique()->numberBetween(1, 100),
        ]);
    }

    private function submitSheet(Entry $entry, int $aroma, int $appearance, int $flavour, int $mouthfeel, int $overall): void
    {
        Scoresheet::create([
            'entry_id' => $entry->id,
            'judge_id' => $this->judge->id,
            'aroma' => $aroma,
            'appearance' => $appearance,
            'flavour' => $flavour,
            'mouthfeel' => $mouthfeel,
            'overall' => $overall,
            'client_uuid' => fake()->uuid(),
            'status' => 'submitted',
        ]);
    }

    public function test_rank_assigns_first_place_to_highest_total(): void
    {
        $high = $this->makeEntry('high@t.com');
        $low = $this->makeEntry('low@t.com');

        $this->submitSheet($high, 12, 3, 20, 5, 10); // total 50
        $this->submitSheet($low, 5, 1, 8, 2, 4);     // total 20

        $ranked = (new CompetitionRankingService)->rank($this->competition);

        $this->assertEquals($high->id, $ranked[0]['entry']->id);
        $this->assertEquals(1, $ranked[0]['placement']);
        $this->assertEquals(2, $ranked[1]['placement']);
    }

    public function test_rank_tiebreak_uses_avg_overall(): void
    {
        $highOverall = $this->makeEntry('high-overall@t.com');
        $lowOverall = $this->makeEntry('low-overall@t.com');

        $this->submitSheet($highOverall, 10, 3, 15, 3, 4); // total 35, overall 4
        $this->submitSheet($lowOverall, 10, 3, 15, 5, 2);  // total 35, overall 2

        $ranked = (new CompetitionRankingService)->rank($this->competition);

        $this->assertEquals($highOverall->id, $ranked[0]['entry']->id);
        $this->assertEquals($lowOverall->id, $ranked[1]['entry']->id);
    }

    public function test_rank_averages_multiple_scoresheets(): void
    {
        $entry = $this->makeEntry('multi@t.com');
        $judge2 = Member::factory()->create(['email' => 'judge2@t.com']);

        Scoresheet::create(['entry_id' => $entry->id, 'judge_id' => $this->judge->id, 'aroma' => 10, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 8, 'client_uuid' => fake()->uuid(), 'status' => 'submitted']);
        Scoresheet::create(['entry_id' => $entry->id, 'judge_id' => $judge2->id, 'aroma' => 8, 'appearance' => 2, 'flavour' => 14, 'mouthfeel' => 3, 'overall' => 6, 'client_uuid' => fake()->uuid(), 'status' => 'submitted']);

        $ranked = (new CompetitionRankingService)->rank($this->competition);

        $this->assertEquals(2, $ranked[0]['sheet_count']);
        $this->assertEquals(round((43 + 33) / 2, 2), $ranked[0]['avg_total']);
    }

    public function test_rank_excludes_draft_scoresheets(): void
    {
        $entry = $this->makeEntry('draft@t.com');

        Scoresheet::create(['entry_id' => $entry->id, 'judge_id' => $this->judge->id, 'aroma' => 12, 'appearance' => 3, 'flavour' => 20, 'mouthfeel' => 5, 'overall' => 10, 'client_uuid' => fake()->uuid(), 'status' => 'draft']);

        $ranked = (new CompetitionRankingService)->rank($this->competition);

        $this->assertEquals(0, $ranked[0]['sheet_count']);
        $this->assertEquals(0.0, $ranked[0]['avg_total']);
    }

    public function test_rank_assigns_correct_band(): void
    {
        $entry = $this->makeEntry('band@t.com');
        $this->submitSheet($entry, 12, 3, 20, 5, 10); // total 50 → Outstanding

        $ranked = (new CompetitionRankingService)->rank($this->competition);

        $this->assertEquals('Outstanding', $ranked[0]['band']);
    }

    public function test_competition_is_closed(): void
    {
        $closed = Competition::create(['name' => 'Closed', 'year' => 2024, 'status' => 'closed']);
        $open = Competition::create(['name' => 'Open', 'year' => 2024, 'status' => 'open']);
        $judging = Competition::create(['name' => 'Judging', 'year' => 2024, 'status' => 'judging']);

        $this->assertTrue($closed->isClosed());
        $this->assertFalse($open->isClosed());
        $this->assertFalse($judging->isClosed());
    }
}
