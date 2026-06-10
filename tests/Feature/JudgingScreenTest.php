<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JudgingScreenTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeFixtures(): array
    {
        $comp = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $brewer = Member::factory()->create(['name' => 'Brewer', 'email' => 'brewer@test.com']);
        $judge = Member::factory()->create(['name' => 'Judge', 'email' => 'judge@test.com']);
        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        $ownEntry = Entry::create(['competition_id' => $comp->id, 'member_id' => $judge->id, 'style_id' => $style->id, 'bottle_number' => 2]);

        return [$comp, $brewer, $judge, $entry, $ownEntry];
    }

    public function test_judging_screen_loads_for_active_member(): void
    {
        [$comp, $brewer, $judge, $entry] = $this->makeFixtures();
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertOk();
    }

    public function test_judging_screen_excludes_judges_own_entries(): void
    {
        [$comp, $brewer, $judge, $entry, $ownEntry] = $this->makeFixtures();
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertOk();
        // Brewer's entry appears in the JSON; judge's own entry does not
        $response->assertSee('"brewer_name":"Brewer"', false);
        $response->assertDontSee('"brewer_name":"Judge"', false);
    }

    public function test_judging_screen_returns_403_when_not_judging(): void
    {
        $comp = Competition::create(['name' => 'Draft Cup', 'year' => 2025, 'status' => 'draft']);
        $judge = Member::factory()->create(['email' => 'j@test.com']);
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $comp = Competition::create(['name' => 'Open Cup', 'year' => 2025, 'status' => 'judging']);
        $response = $this->get("/judging/{$comp->id}");
        $response->assertRedirect('/login');
    }
}
