<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JudgeReportAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array{0: Competition, 1: Member, 2: Member, 3: Member, 4: Entry}
     */
    private function seedData(): array
    {
        $style = Style::create(['code' => '21A', 'category_name' => 'IPA', 'name' => 'American IPA']);
        $judgeOne = Member::factory()->create(['name' => 'Judge One', 'email' => 'judge1@t.com']);
        $judgeTwo = Member::factory()->create(['name' => 'Judge Two', 'email' => 'judge2@t.com']);
        $brewer = Member::factory()->create(['name' => 'Brewer', 'email' => 'brewer@t.com']);
        $admin = Member::factory()->create(['name' => 'Admin', 'email' => 'admin@t.com', 'is_admin' => true]);

        $comp = Competition::create(['name' => 'Autumn Harvest', 'year' => 2026, 'status' => 'closed']);
        $comp->styles()->attach($style->id);
        $comp->judges()->attach([$judgeOne->id, $judgeTwo->id]);

        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);

        // Judge one scores generously (total 44); judge two scores lower (total 27) — a 17-point gap, an outlier.
        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $judgeOne->id,
            'aroma' => 10, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 9,
            'client_uuid' => fake()->uuid(), 'status' => 'submitted',
        ]);
        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $judgeTwo->id,
            'aroma' => 6, 'appearance' => 2, 'flavour' => 10, 'mouthfeel' => 3, 'overall' => 6,
            'client_uuid' => fake()->uuid(), 'status' => 'submitted',
        ]);

        return [$comp, $judgeOne, $judgeTwo, $admin, $entry];
    }

    public function test_judge_can_view_own_report_when_closed(): void
    {
        [$comp, $judgeOne] = $this->seedData();
        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeOne->id}");
        $response->assertOk();
    }

    public function test_admin_can_view_any_judge_report_before_closed(): void
    {
        $style = Style::create(['code' => '25B', 'category_name' => 'SA', 'name' => 'Saison']);
        $judge = Member::factory()->create(['name' => 'J', 'email' => 'j@t.com']);
        $admin = Member::factory()->create(['name' => 'A', 'email' => 'a@t.com', 'is_admin' => true]);
        $comp = Competition::create(['name' => 'Open Cup', 'year' => 2026, 'status' => 'judging']);
        $comp->judges()->attach($judge->id);

        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/judge-report/{$judge->id}");
        $response->assertOk();
    }

    public function test_judge_cannot_view_another_judges_report(): void
    {
        [$comp, $judgeOne, $judgeTwo] = $this->seedData();
        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeTwo->id}");
        $response->assertForbidden();
    }

    public function test_judge_report_blocked_if_competition_not_closed(): void
    {
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $judge = Member::factory()->create(['name' => 'J2', 'email' => 'j2@t.com']);
        $comp = Competition::create(['name' => 'Judging Cup', 'year' => 2026, 'status' => 'judging']);
        $comp->judges()->attach($judge->id);

        $response = $this->actingAs($judge)->get("/competitions/{$comp->id}/judge-report/{$judge->id}");
        $response->assertForbidden();
    }

    public function test_report_404s_for_a_member_who_never_judged(): void
    {
        [$comp, , , $admin] = $this->seedData();
        $notAJudge = Member::factory()->create(['name' => 'Bystander', 'email' => 'bystander@t.com']);

        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/judge-report/{$notAJudge->id}");
        $response->assertNotFound();
    }

    public function test_report_flags_large_score_gap_as_outlier(): void
    {
        [$comp, $judgeOne] = $this->seedData();
        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeOne->id}");

        $response->assertOk();
        $response->assertSee('outlier');
        $response->assertSee('+17.0');
    }

    public function test_report_shows_full_standings(): void
    {
        [$comp, $judgeOne, , , $entry] = $this->seedData();
        $response = $this->actingAs($judgeOne)->get("/competitions/{$comp->id}/judge-report/{$judgeOne->id}");

        $response->assertOk();
        $response->assertSee('Full Standings');
        $response->assertSee($entry->member?->name);
    }
}
