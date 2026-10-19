<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResultsLocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{0: Competition, 1: Member, 2: Member, 3: Member}
     */
    private function seedClosedCompetition(int $secondJudgeTotalOffset = 0): array
    {
        $style = Style::create(['code' => '21A', 'category_name' => 'IPA', 'name' => 'American IPA']);
        $judgeOne = Member::factory()->create(['name' => 'Judge One', 'email' => 'judge1@t.com', 'locale' => 'nl']);
        $judgeTwo = Member::factory()->create(['name' => 'Judge Two', 'email' => 'judge2@t.com']);
        $brewer = Member::factory()->create(['name' => 'Brewer', 'email' => 'brewer@t.com', 'locale' => 'nl']);

        $comp = Competition::forceCreate(['name' => 'Najaar', 'year' => 2024, 'status' => 'closed', 'judging_date' => '2024-10-19']);
        $comp->styles()->attach($style->id);
        $comp->judges()->attach([$judgeOne->id, $judgeTwo->id]);

        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1, 'abv' => 6.5]);

        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $judgeOne->id,
            'aroma' => 10, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 9,
            'aroma_notes' => 'Fruitig', 'appearance_notes' => 'Helder', 'flavour_notes' => 'Hoppig', 'mouthfeel_notes' => 'Vol', 'overall_notes' => 'Lekker', 'client_uuid' => fake()->uuid(), 'status' => 'submitted',
        ]);
        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $judgeTwo->id,
            'aroma' => 6 + $secondJudgeTotalOffset, 'appearance' => 2, 'flavour' => 10 + $secondJudgeTotalOffset * 2, 'mouthfeel' => 3, 'overall' => 6 + $secondJudgeTotalOffset,
            'client_uuid' => fake()->uuid(), 'status' => 'submitted',
        ]);

        return [$comp, $judgeOne, $judgeTwo, $brewer];
    }

    public function test_dutch_member_sees_dutch_results_index(): void
    {
        [$comp, $judgeOne] = $this->seedClosedCompetition();

        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/results");

        $response->assertOk()
            ->assertSee('<title>Resultaten — Najaar', false)
            ->assertSeeText('Najaar — Resultaten')
            ->assertSeeText('Volledige ranglijst')
            ->assertSeeText('Brouwer')
            ->assertSeeText('Formulieren')
            ->assertSeeText('Goed')
            ->assertSeeText('6.5% ABV')
            ->assertSeeText('Jouw juryrapport')
            ->assertSeeText('Bekijk juryrapport')
            ->assertDontSeeText('Full Standings')
            ->assertDontSeeText('Your judge report')
            ->assertDontSeeText('View judge report')
            ->assertDontSeeText('Sheets')
            ->assertDontSeeText('Good');
    }

    public function test_dutch_admin_sees_dutch_admin_results_index(): void
    {
        [$comp] = $this->seedClosedCompetition();
        $admin = Member::factory()->admin()->create(['locale' => 'nl']);

        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results");

        $response->assertOk()
            ->assertSeeText('1 deelnemer')
            ->assertSeeText('Alle resultaten')
            ->assertSeeText('Juryrapporten')
            ->assertSeeText('Bekijk formulier')
            ->assertDontSeeText('participants')
            ->assertDontSeeText('Full results')
            ->assertDontSeeText('Judge Reports')
            ->assertDontSeeText('View sheet');
    }

    public function test_dutch_member_sees_dutch_score_sheet(): void
    {
        [$comp, , , $brewer] = $this->seedClosedCompetition();

        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results/{$brewer->id}");

        $response->assertOk()
            ->assertSee('<title>Resultaten — Najaar — Brewer', false)
            ->assertSeeText('Najaar — Resultaten')
            ->assertSeeText('Afdrukken')
            ->assertSeeText('← Alle resultaten')
            ->assertSeeText('19 oktober 2024')
            ->assertSeeText('Jouw inzendingen')
            ->assertSeeText('Fles #1')
            ->assertSeeText('2 juryleden')
            ->assertSeeText('1e van 1')
            ->assertSeeText('Goed')
            ->assertSeeText('Jurylid')
            ->assertSeeText('Uiterlijk Helder')
            ->assertSeeText('Smaak Hoppig')
            ->assertSeeText('Mondgevoel Vol')
            ->assertSeeText('Algeheel Lekker')
            ->assertSeeText('Klik op een rij met notities')
            ->assertSeeText('Volledige ranglijst')
            ->assertDontSeeText('Full Standings')
            ->assertDontSeeText('All results')
            ->assertDontSeeText('Print')
            ->assertDontSeeText('1st')
            ->assertDontSeeText('Your Entries')
            ->assertDontSeeText('Bottle #')
            ->assertDontSeeText('October 19, 2024');
    }

    public function test_dutch_member_sees_dutch_judge_report_with_outliers(): void
    {
        [$comp, $judgeOne] = $this->seedClosedCompetition();

        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeOne->id}");

        $response->assertOk()
            ->assertSee('<title>Juryrapport — Najaar — Judge One', false)
            ->assertSeeText('Najaar — Juryrapport')
            ->assertSeeText('19 oktober 2024')
            ->assertSeeText('Jouw keurprofiel')
            ->assertSeeText('1 inzending gekeurd')
            ->assertSeeText('grootste verschil · #1 American IPA (21A)')
            ->assertSeeText('Eén inzending week af:')
            ->assertSeeText('(+17.0, je gaf een hogere score)')
            ->assertSeeText('— zie hieronder.')
            ->assertSeeText('uitschieter')
            ->assertSeeText('Jouw scores vergeleken met de rest')
            ->assertSeeText('Jouw totaal')
            ->assertSeeText('Uiterlijk /3')
            ->assertSeeText('Algeheel /10')
            ->assertSeeText('Jij 10 · Gem. 6.0')
            ->assertSeeText('Volledige ranglijst')
            ->assertDontSeeText('Judge Report')
            ->assertDontSeeText('All results')
            ->assertDontSeeText('outlier')
            ->assertDontSeeText('stood out')
            ->assertDontSeeText('Your Judging Profile')
            ->assertDontSeeText('October 19, 2024');
    }

    public function test_dutch_judge_report_without_outliers_shows_consistent_sentence(): void
    {
        [$comp, $judgeOne] = $this->seedClosedCompetition(secondJudgeTotalOffset: 4);

        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeOne->id}");

        $response->assertOk()
            ->assertSeeText('Je scores kwamen bij alle inzendingen overeen met de rest van de jury.')
            ->assertDontSeeText('Your scores were consistent')
            ->assertDontSeeText('week af');
    }
}
