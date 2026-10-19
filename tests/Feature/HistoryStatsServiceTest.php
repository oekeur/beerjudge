<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use App\Services\HistoryStatsService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HistoryStatsServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Style $ipa;

    private Style $stout;

    private Member $judge;

    private Member $otherJudge;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ipa = Style::create(['code' => '21A', 'category_name' => 'IPA', 'name' => 'American IPA']);
        $this->stout = Style::create(['code' => '20A', 'category_name' => 'Stout', 'name' => 'Stout']);
        $this->judge = Member::factory()->create(['email' => 'judge@t.com']);
        $this->otherJudge = Member::factory()->create(['email' => 'judge2@t.com']);
    }

    private function service(): HistoryStatsService
    {
        return app(HistoryStatsService::class);
    }

    private function competition(string $name, string $status = 'closed', ?string $date = null): Competition
    {
        $competition = Competition::forceCreate(['name' => $name, 'year' => 2025, 'status' => $status, 'judging_date' => $date]);
        $competition->styles()->attach([$this->ipa->id, $this->stout->id]);

        return $competition;
    }

    private function entry(Competition $competition, Member $member, ?Style $style = null, ?int $bottle = 1): Entry
    {
        return Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $member->id,
            'style_id' => ($style ?? $this->ipa)->id,
            'bottle_number' => $bottle,
        ]);
    }

    /** Sheet whose total equals the given number: 50 points split as aroma 12 + the rest in flavour/overall. */
    private function sheet(Entry $entry, int $total, int $overall = 5, string $status = 'submitted', ?Member $judge = null): void
    {
        $remaining = $total - $overall;
        $sections = [];
        foreach (['flavour' => 20, 'aroma' => 12, 'mouthfeel' => 5, 'appearance' => 3] as $section => $max) {
            $sections[$section] = min($max, $remaining);
            $remaining -= $sections[$section];
        }

        Scoresheet::create([
            'entry_id' => $entry->id,
            'judge_id' => ($judge ?? $this->judge)->id,
            'aroma' => $sections['aroma'],
            'appearance' => $sections['appearance'],
            'flavour' => $sections['flavour'],
            'mouthfeel' => $sections['mouthfeel'],
            'overall' => $overall,
            'client_uuid' => fake()->uuid(),
            'status' => $status,
        ]);
    }

    private function brewer(string $name): Member
    {
        return Member::factory()->create(['name' => $name, 'email' => strtolower($name).'@t.com']);
    }

    public function test_non_closed_competition_contributes_nothing(): void
    {
        $open = $this->competition('Judging Cup', 'judging');
        $entry = $this->entry($open, $this->brewer('Anna'));
        $this->sheet($entry, 40);

        $service = $this->service();

        $this->assertCount(0, $service->competitionSummaries());
        $this->assertSame(0, array_sum($service->scoreDistribution()));
        $this->assertCount(0, $service->mostEnteredStyles());
        $this->assertCount(0, $service->styleStats());
        $this->assertSame([], $service->memberHistory($entry->member)['rows']);
        $this->assertSame([], $service->styleTrend($this->ipa));
    }

    public function test_unscored_entry_counts_in_entries_and_styles_but_not_averages_or_distribution(): void
    {
        $competition = $this->competition('Cup');
        $scored = $this->entry($competition, $this->brewer('Anna'), $this->ipa, 1);
        $this->entry($competition, $this->brewer('Bob'), $this->stout, 2);
        $this->entry($competition, $this->brewer('Cleo'), $this->stout, 3);
        $this->sheet($scored, 40);

        $service = $this->service();
        $summary = $service->competitionSummaries()->first();

        $this->assertSame(3, $summary['entries']);
        $this->assertSame(1, $summary['scored_entries']);
        $this->assertSame(3, $summary['participants']);
        $this->assertSame(1, $summary['judges']);
        $this->assertSame(40.0, $summary['average_total']);
        $this->assertSame(1, array_sum($service->scoreDistribution()));
        $this->assertSame(1, $service->scoreDistribution()['excellent']);

        $styles = $service->mostEnteredStyles();
        $this->assertSame(['20A Stout', 2], [$styles[0]['label'], $styles[0]['entries']]);
        $this->assertSame(['21A American IPA', 1], [$styles[1]['label'], $styles[1]['entries']]);

        $stats = $service->styleStats();
        $this->assertCount(1, $stats);
        $this->assertSame($this->ipa->id, $stats[0]['style_id']);
    }

    public function test_draft_sheet_is_ignored(): void
    {
        $competition = $this->competition('Cup');
        $entry = $this->entry($competition, $this->brewer('Anna'));
        $this->sheet($entry, 40, 5, 'submitted');
        $this->sheet($entry, 10, 2, 'draft', $this->otherJudge);

        $summary = $this->service()->competitionSummaries()->first();

        $this->assertSame(40.0, $summary['average_total']);
        $this->assertSame(1, $summary['judges']);
    }

    public function test_winner_is_highest_average_and_scores_are_means_of_entry_averages(): void
    {
        $competition = $this->competition('Cup');
        $low = $this->entry($competition, $this->brewer('Anna'), $this->ipa, 1);
        $high = $this->entry($competition, $this->brewer('Bob'), $this->stout, 2);
        $this->sheet($low, 30);
        $this->sheet($high, 44);
        $this->sheet($high, 40, 5, 'submitted', $this->otherJudge);

        $summary = $this->service()->competitionSummaries()->first();

        $this->assertSame($high->id, $summary['winner']['entry_id']);
        $this->assertSame('Bob', $summary['winner']['member_name']);
        $this->assertSame('20A Stout', $summary['winner']['style_label']);
        $this->assertSame(42.0, $summary['winner']['avg_total']);
        $this->assertFalse($summary['winner']['is_tied_for_first']);
        $this->assertSame(36.0, $summary['average_total']);
        $this->assertSame(2, $summary['judges']);
    }

    public function test_full_tie_sets_is_tied_for_first(): void
    {
        $competition = $this->competition('Cup');
        $this->sheet($this->entry($competition, $this->brewer('Anna'), $this->ipa, 1), 40, 6);
        $this->sheet($this->entry($competition, $this->brewer('Bob'), $this->stout, 2), 40, 6);

        $this->assertTrue($this->service()->competitionSummaries()->first()['winner']['is_tied_for_first']);
    }

    public function test_same_total_with_different_overall_is_not_a_tie(): void
    {
        $competition = $this->competition('Cup');
        $this->sheet($this->entry($competition, $this->brewer('Anna'), $this->ipa, 1), 40, 6);
        $this->sheet($this->entry($competition, $this->brewer('Bob'), $this->stout, 2), 40, 5);

        $this->assertFalse($this->service()->competitionSummaries()->first()['winner']['is_tied_for_first']);
    }

    public function test_score_distribution_always_has_six_keys(): void
    {
        $empty = $this->service()->scoreDistribution();

        $this->assertSame(
            ['outstanding', 'excellent', 'very_good', 'good', 'fair', 'problematic'],
            array_keys($empty),
        );
        $this->assertSame(0, array_sum($empty));

        $competition = $this->competition('Cup');
        $this->sheet($this->entry($competition, $this->brewer('Anna')), 46);

        $filled = $this->service()->scoreDistribution();
        $this->assertCount(6, $filled);
        $this->assertSame(1, $filled['outstanding']);
    }

    public function test_closed_competition_without_scored_entries_has_null_winner_and_average(): void
    {
        $competition = $this->competition('Cup');
        $this->entry($competition, $this->brewer('Anna'));

        $summary = $this->service()->competitionSummaries()->first();

        $this->assertNull($summary['winner']);
        $this->assertNull($summary['average_total']);
        $this->assertSame(1, $summary['entries']);
        $this->assertSame(0, $summary['scored_entries']);
    }

    public function test_competitions_are_ordered_by_judging_date_nulls_last(): void
    {
        $undated = $this->competition('Undated', 'closed', null);
        $late = $this->competition('Late', 'closed', '2025-09-01');
        $early = $this->competition('Early', 'closed', '2025-03-01');

        $this->assertSame(
            [$early->id, $late->id, $undated->id],
            $this->service()->competitionSummaries()->pluck('competition_id')->all(),
        );
    }

    public function test_member_history_sorts_best_desc_and_averages_by_style(): void
    {
        $anna = $this->brewer('Anna');
        $bob = $this->brewer('Bob');
        $first = $this->competition('First', 'closed', '2025-03-01');
        $second = $this->competition('Second', 'closed', '2025-06-01');

        $a1 = $this->entry($first, $anna, $this->ipa, 1);
        $a2 = $this->entry($second, $anna, $this->ipa, 1);
        $a3 = $this->entry($second, $anna, $this->stout, 2);
        $porter = Style::create(['code' => '20B', 'category_name' => 'Stout', 'name' => 'Porter']);
        $second->styles()->attach($porter->id);
        $a4 = $this->entry($second, $anna, $porter, 3);
        $b1 = $this->entry($second, $bob, $this->stout, 4);
        $this->sheet($a1, 30);
        $this->sheet($a2, 40);
        $this->sheet($a3, 44);
        $this->sheet($b1, 35);

        $history = $this->service()->memberHistory($anna);

        $this->assertCount(4, $history['rows']);
        $this->assertSame([44.0, 40.0, 30.0], array_column($history['best'], 'avg_total'));

        $byStyle = collect($history['by_style'])->keyBy('style_id');
        $this->assertSame(35.0, $byStyle[$this->ipa->id]['average_total']);
        $this->assertSame(2, $byStyle[$this->ipa->id]['entries']);
        $this->assertSame(44.0, $byStyle[$this->stout->id]['average_total']);
        $this->assertSame(1, $byStyle[$this->stout->id]['entries']);

        $this->assertSame(
            [['First', 30.0], ['Second', 42.0]],
            array_map(fn (array $point): array => [$point['name'], $point['average_total']], $history['trend']),
        );

        $secondIpa = collect($history['rows'])->firstWhere('entry_id', $a2->id);
        $this->assertSame(2, $secondIpa['placement']);
        $this->assertSame(4, $secondIpa['entrants']);

        $unscored = collect($history['rows'])->firstWhere('entry_id', $a4->id);
        $this->assertNull($unscored['avg_total']);
        $this->assertNull($unscored['placement']);
    }

    public function test_entrants_counts_every_ranked_entry_including_unscored(): void
    {
        $anna = $this->brewer('Anna');
        $competition = $this->competition('Cup');
        $this->sheet($this->entry($competition, $anna, $this->ipa, 1), 40);
        $this->sheet($this->entry($competition, $this->brewer('Bob'), $this->ipa, 2), 30);
        $this->entry($competition, $this->brewer('Cleo'), $this->ipa, 3);

        $row = $this->service()->memberHistory($anna)['rows'][0];

        $this->assertSame(1, $row['placement']);
        $this->assertSame(3, $row['entrants']);
    }

    public function test_scored_entry_with_zero_total_ties_with_unscored(): void
    {
        $competition = $this->competition('Cup');
        $this->entry($competition, $this->brewer('Unscored'));
        $zero = $this->entry($competition, $this->brewer('Zero'));
        Scoresheet::create(['entry_id' => $zero->id, 'judge_id' => $this->judge->id, 'aroma' => 0, 'appearance' => 0, 'flavour' => 0, 'mouthfeel' => 0, 'overall' => 0, 'client_uuid' => fake()->uuid(), 'status' => 'submitted']);

        $row = $this->service()->memberHistory($zero->member)['rows'][0];

        $this->assertLessThanOrEqual($row['entrants'], $row['placement']);
    }

    public function test_best_is_capped_at_five(): void
    {
        $anna = $this->brewer('Anna');
        foreach ([20, 25, 30, 35, 40, 45] as $i => $total) {
            $this->sheet($this->entry($this->competition("Cup {$i}", 'closed', '2025-0'.($i + 1).'-01'), $anna), $total);
        }

        $best = $this->service()->memberHistory($anna)['best'];

        $this->assertSame([45.0, 40.0, 35.0, 30.0, 25.0], array_column($best, 'avg_total'));
    }

    public function test_style_stats_separates_same_name_with_different_source(): void
    {
        $custom = Style::create(['code' => 'C1', 'category_name' => 'IPA', 'name' => 'American IPA', 'source' => 'custom']);
        $competition = $this->competition('Cup');
        $competition->styles()->attach($custom->id);

        $this->sheet($this->entry($competition, $this->brewer('Anna'), $this->ipa, 1), 40);
        $this->sheet($this->entry($competition, $this->brewer('Bob'), $custom, 2), 30);
        $this->sheet($this->entry($competition, $this->brewer('Cleo'), $custom, 3), 36);

        $stats = $this->service()->styleStats()->keyBy('style_id');

        $this->assertCount(2, $stats);
        $this->assertSame(2, $stats[$custom->id]['scored_entries']);
        $this->assertSame(33.0, $stats[$custom->id]['average_total']);
        $this->assertSame(30.0, $stats[$custom->id]['min_total']);
        $this->assertSame(36.0, $stats[$custom->id]['max_total']);
        $this->assertSame(1, $stats[$this->ipa->id]['scored_entries']);
        $this->assertSame($custom->id, $this->service()->styleStats()->first()['style_id']);
    }

    public function test_style_trend_lists_competitions_with_scored_entries_of_that_style(): void
    {
        $first = $this->competition('First', 'closed', '2025-03-01');
        $second = $this->competition('Second', 'closed', '2025-06-01');
        $third = $this->competition('Third', 'closed', '2025-09-01');

        $this->sheet($this->entry($first, $this->brewer('Anna'), $this->ipa, 1), 30);
        $this->sheet($this->entry($second, $this->brewer('Bob'), $this->stout, 1), 40);
        $this->sheet($this->entry($third, $this->brewer('Cleo'), $this->ipa, 1), 36);
        $this->sheet($this->entry($third, $this->brewer('Dan'), $this->ipa, 2), 38);

        $trend = $this->service()->styleTrend($this->ipa);

        $this->assertSame(
            [['First', 30.0, 1], ['Third', 37.0, 2]],
            array_map(fn (array $point): array => [$point['name'], $point['average_total'], $point['scored_entries']], $trend),
        );
    }

    public function test_backfilled_shaped_data_still_counts(): void
    {
        $competition = $this->competition('Backfilled');
        $entry = $this->entry($competition, $this->brewer('Anna'), $this->ipa, null);
        $this->sheet($entry, 38);

        $this->assertDatabaseCount('competition_judge', 0);
        $this->assertNull($entry->fresh()->bottle_number);

        $summary = $this->service()->competitionSummaries()->first();

        $this->assertSame(1, $summary['scored_entries']);
        $this->assertSame(38.0, $summary['average_total']);
        $this->assertSame(1, $summary['judges']);
        $this->assertSame($entry->id, $summary['winner']['entry_id']);
    }
}
