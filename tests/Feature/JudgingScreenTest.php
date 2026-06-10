<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JudgingScreenTest extends TestCase
{
    use RefreshDatabase;

    private function makeFixtures(): array
    {
        $comp = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $brewer = Member::create(['name' => 'Brewer', 'email' => 'brewer@test.com', 'password' => bcrypt('pw'), 'is_active' => true]);
        $judge = Member::create(['name' => 'Judge', 'email' => 'judge@test.com', 'password' => bcrypt('pw'), 'is_active' => true]);
        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'name' => 'Pale Ale', 'bottle_number' => 1]);
        $ownEntry = Entry::create(['competition_id' => $comp->id, 'member_id' => $judge->id, 'style_id' => $style->id, 'name' => 'Judge Beer', 'bottle_number' => 2]);
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
        // The own entry's name should not appear in the server-rendered JSON blob
        $response->assertDontSee('Judge Beer');
        $response->assertSee('Pale Ale');
    }

    public function test_judging_screen_returns_403_when_not_judging(): void
    {
        $comp = Competition::create(['name' => 'Draft Cup', 'year' => 2025, 'status' => 'draft']);
        $judge = Member::create(['name' => 'Judge', 'email' => 'j@test.com', 'password' => bcrypt('pw'), 'is_active' => true]);
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
