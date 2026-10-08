<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class JudgingScreenLocaleTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function competitionWithEntryFor(Member $judge): Competition
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create(['email' => 'brewer@test.com']);
        Entry::create(['competition_id' => $competition->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);

        return $competition;
    }

    public function test_dutch_member_gets_dutch_accessible_labels(): void
    {
        $judge = Member::factory()->create(['locale' => 'nl']);
        $competition = $this->competitionWithEntryFor($judge);

        $response = $this->actingAs($judge)->get("/judging/{$competition->id}");

        $response->assertOk();
        $response->assertSee('aria-label="Aroma score, 0 tot 12"', false);
        $response->assertSee('aria-label="Uiterlijk notities"', false);
        $response->assertSee('Wacht op verbinding');
        $response->assertDontSee('aria-label="Aroma score, 0 to 12"', false);
    }

    public function test_english_member_gets_english_accessible_labels(): void
    {
        $judge = Member::factory()->create(['locale' => 'en']);
        $competition = $this->competitionWithEntryFor($judge);

        $response = $this->actingAs($judge)->get("/judging/{$competition->id}");

        $response->assertOk();
        $response->assertSee('aria-label="Aroma score, 0 to 12"', false);
        $response->assertSee('aria-label="Appearance notes"', false);
        $response->assertSee('Waiting for connection');
    }
}
