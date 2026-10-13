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
        $comp = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $comp->styles()->attach($style->id);
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

    public function test_tasting_numbers_are_the_same_for_every_judge(): void
    {
        $comp = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $comp->styles()->attach($style->id);
        $brewers = Member::factory()->count(3)->create();
        $outsider = Member::factory()->create();
        $entries = $brewers->values()->map(fn (Member $m, int $i) => Entry::create([
            'competition_id' => $comp->id, 'member_id' => $m->id, 'style_id' => $style->id, 'bottle_number' => $i + 1, 'sort_order' => $i + 1,
        ]));

        $numbersFor = function (Member $judge) use ($comp): array {
            $data = $this->actingAs($judge)->get("/judging/{$comp->id}")->viewData('serverData');

            return collect($data['entries'])->concat($data['ownEntries'])->pluck('tasting_number', 'id')->sortKeys()->all();
        };

        $expected = [$entries[0]->id => 1, $entries[1]->id => 2, $entries[2]->id => 3];
        $this->assertSame($expected, $numbersFor($outsider));
        $this->assertSame($expected, $numbersFor($brewers[1]));
    }

    public function test_own_entry_is_sent_as_a_locked_row_without_brewer_or_style_guide(): void
    {
        [$comp, $brewer, $judge, $entry, $ownEntry] = $this->makeFixtures();
        $data = $this->actingAs($judge)->get("/judging/{$comp->id}")->viewData('serverData');

        $this->assertSame([$entry->id], collect($data['entries'])->pluck('id')->all());
        $this->assertSame([[
            'id' => $ownEntry->id,
            'tasting_number' => 2,
            'style_name' => 'Light Lager',
            'is_own' => true,
        ]], collect($data['ownEntries'])->all());
    }

    public function test_every_notes_field_caps_input_at_the_server_limit(): void
    {
        [$comp, $brewer, $judge] = $this->makeFixtures();
        $html = $this->actingAs($judge)->get("/judging/{$comp->id}")->assertOk()->getContent();

        $this->assertSame(5, substr_count($html, '<textarea'));
        $this->assertSame(5, substr_count($html, 'maxlength="2000"'));
    }

    public function test_judging_screen_returns_403_when_not_judging(): void
    {
        $comp = Competition::forceCreate(['name' => 'Draft Cup', 'year' => 2025, 'status' => 'draft']);
        $judge = Member::factory()->create(['email' => 'j@test.com']);
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected(): void
    {
        $comp = Competition::forceCreate(['name' => 'Open Cup', 'year' => 2025, 'status' => 'judging']);
        $response = $this->get("/judging/{$comp->id}");
        $response->assertRedirect('/login');
    }

    public function test_judging_screen_includes_style_info_fields(): void
    {
        [$comp, $brewer, $judge, $entry] = $this->makeFixtures();
        Style::where('code', '1A')->update([
            'category_description' => 'Standard American beers are easy-drinking, approachable styles.',
            'history' => 'Developed post-Prohibition in the United States.',
            'style_comparison' => 'Similar to International Pale Lager but with even less character.',
        ]);
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertOk();
        $response->assertSee('"category_description"', false);
        $response->assertSee('"history"', false);
        $response->assertSee('"style_comparison"', false);
    }

    public function test_judging_screen_includes_srm_fields_when_set(): void
    {
        [$comp, $brewer, $judge, $entry] = $this->makeFixtures();
        Style::where('code', '1A')->update(['srm_min' => 2.0, 'srm_max' => 4.0]);
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertOk();
        $response->assertSee('"srm_min":2', false);
        $response->assertSee('"srm_max":4', false);
    }

    public function test_judging_screen_srm_fields_are_null_when_not_set(): void
    {
        [$comp, $brewer, $judge, $entry] = $this->makeFixtures();
        // Style created in makeFixtures has no srm_min/srm_max set
        $response = $this->actingAs($judge)->get("/judging/{$comp->id}");
        $response->assertOk();
        $response->assertSee('"srm_min":null', false);
        $response->assertSee('"srm_max":null', false);
    }
}
