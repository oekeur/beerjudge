<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ResultsAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function seedData(): array
    {
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $admin = Member::factory()->create(['name' => 'Admin', 'email' => 'admin@t.com', 'is_admin' => true]);
        $brewer = Member::factory()->create(['name' => 'Brewer', 'email' => 'brewer@t.com']);
        $other = Member::factory()->create(['name' => 'Other', 'email' => 'other@t.com']);
        $comp = Competition::create(['name' => 'Spring Cup', 'year' => 2025, 'status' => 'closed']);
        $comp->styles()->attach($style->id);
        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $admin->id,
            'aroma' => 10, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 9,
            'client_uuid' => fake()->uuid(), 'status' => 'submitted',
        ]);

        return [$comp, $brewer, $admin, $other, $entry];
    }

    public function test_admin_can_view_results_immediately(): void
    {
        [$comp, $brewer, $admin] = $this->seedData();
        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results/{$brewer->id}");
        $response->assertOk();
    }

    public function test_member_can_view_own_sheet_when_closed(): void
    {
        [$comp, $brewer] = $this->seedData();
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results/{$brewer->id}");
        $response->assertOk();
    }

    public function test_member_cannot_view_other_members_sheet(): void
    {
        [$comp, $brewer, $admin, $other] = $this->seedData();
        $response = $this->actingAs($other)->get("/competitions/{$comp->id}/results/{$brewer->id}");
        $response->assertForbidden();
    }

    public function test_member_results_blocked_if_competition_not_closed(): void
    {
        $style = Style::create(['code' => '2A', 'category_name' => 'SA', 'name' => 'Intl Lager']);
        $comp = Competition::create(['name' => 'Open Cup', 'year' => 2025, 'status' => 'judging']);
        $brewer = Member::factory()->create(['name' => 'B2', 'email' => 'b2@t.com']);
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results/{$brewer->id}");
        $response->assertForbidden();
    }

    public function test_admin_can_view_sheet_before_competition_closes(): void
    {
        $style = Style::create(['code' => '2B', 'category_name' => 'SA', 'name' => 'Intl Pale Lager']);
        $admin = Member::factory()->create(['name' => 'Admin2', 'email' => 'a2@t.com', 'is_admin' => true]);
        $comp = Competition::create(['name' => 'Judging Cup', 'year' => 2025, 'status' => 'judging']);
        $comp->styles()->attach($style->id);
        $brewer = Member::factory()->create(['name' => 'B2', 'email' => 'b2@t.com']);
        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results/{$brewer->id}");
        $response->assertOk();
    }

    public function test_admin_can_view_index_before_competition_closes(): void
    {
        $admin = Member::factory()->create(['name' => 'Admin3', 'email' => 'a3@t.com', 'is_admin' => true]);
        $comp = Competition::create(['name' => 'Draft Cup', 'year' => 2025, 'status' => 'judging']);
        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
    }

    public function test_member_can_view_results_index_when_closed(): void
    {
        [$comp, $brewer] = $this->seedData();
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
    }

    public function test_member_index_blocked_if_competition_not_closed(): void
    {
        $comp = Competition::create(['name' => 'Open Cup 2', 'year' => 2025, 'status' => 'judging']);
        $brewer = Member::factory()->create(['name' => 'B3', 'email' => 'b3@t.com']);
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results");
        $response->assertForbidden();
    }

    public function test_uninvolved_member_can_view_index_and_sees_only_full_standings(): void
    {
        [$comp, $brewer, $admin, $other] = $this->seedData();
        $response = $this->actingAs($other)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
        $response->assertSee('Full Standings');
        $response->assertDontSee(route('results.show', [$comp, $brewer]), false);
        $response->assertDontSee('View sheet');
        $response->assertDontSee('View judge report');
    }

    public function test_member_index_shows_own_entry_link_but_not_other_members(): void
    {
        [$comp, $brewer, $admin, $other] = $this->seedData();
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
        $response->assertSee(route('results.show', [$comp, $brewer]), false);
        $response->assertDontSee($other->name);
    }

    public function test_member_index_shows_own_judge_report_link_when_viewer_judged(): void
    {
        [$comp, $brewer, $admin, $other] = $this->seedData();
        $response = $this->actingAs($other)->get("/competitions/{$comp->id}/results");
        $response->assertDontSee('View judge report');

        $comp->judges()->attach($other->id);
        $response = $this->actingAs($other)->get("/competitions/{$comp->id}/results");
        $response->assertSee(route('judge-report.show', [$comp, $other]), false);
    }

    public function test_member_index_does_not_show_other_members_report_links(): void
    {
        [$comp, $brewer, $admin, $other] = $this->seedData();
        $comp->judges()->attach($other->id);
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results");
        $response->assertDontSee(route('judge-report.show', [$comp, $other]), false);
        $response->assertDontSee(route('results.show', [$comp, $other]), false);
    }

    public function test_member_index_shows_full_standings(): void
    {
        [$comp, $brewer] = $this->seedData();
        $response = $this->actingAs($brewer)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
        $response->assertSee('Full Standings');
    }

    public function test_admin_index_still_shows_all_members_and_judges(): void
    {
        [$comp, $brewer, $admin] = $this->seedData();
        $comp->judges()->attach($admin->id);
        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results");
        $response->assertOk();
        $response->assertSee('Full results');
        $response->assertSee('Your judge report');
        $response->assertSee($brewer->name);
        $response->assertSee($admin->name);
        $response->assertSee(route('results.show', [$comp, $brewer]), false);
        $response->assertSee(route('judge-report.show', [$comp, $admin]), false);
    }

    public function test_ranking_orders_by_average_total_descending(): void
    {
        $style = Style::create(['code' => '3A', 'category_name' => 'CZ', 'name' => 'Czech Pale Lager']);
        $admin = Member::factory()->create(['name' => 'Admin3', 'email' => 'a3@t.com', 'is_admin' => true]);
        $b1 = Member::factory()->create(['name' => 'High', 'email' => 'high@t.com']);
        $b2 = Member::factory()->create(['name' => 'Low', 'email' => 'low@t.com']);
        $comp = Competition::create(['name' => 'Rank Cup', 'year' => 2025, 'status' => 'closed']);
        $comp->styles()->attach($style->id);
        $e1 = Entry::create(['competition_id' => $comp->id, 'member_id' => $b1->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        $e2 = Entry::create(['competition_id' => $comp->id, 'member_id' => $b2->id, 'style_id' => $style->id, 'bottle_number' => 2]);
        Scoresheet::create(['entry_id' => $e1->id, 'judge_id' => $admin->id, 'aroma' => 12, 'appearance' => 3, 'flavour' => 20, 'mouthfeel' => 5, 'overall' => 10, 'client_uuid' => fake()->uuid(), 'status' => 'submitted']);
        Scoresheet::create(['entry_id' => $e2->id, 'judge_id' => $admin->id, 'aroma' => 5, 'appearance' => 2, 'flavour' => 10, 'mouthfeel' => 3, 'overall' => 5, 'client_uuid' => fake()->uuid(), 'status' => 'submitted']);

        $response = $this->actingAs($admin)->get("/competitions/{$comp->id}/results/{$b1->id}");
        $response->assertOk();
        // High (50 pts) should appear before Low (25 pts) in the standings
        $this->assertLessThan(
            strpos($response->getContent(), 'Low'),
            strpos($response->getContent(), 'High')
        );
    }
}
