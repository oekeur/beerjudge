<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScoresheetValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $comp;

    private Entry $entry;

    private Member $judge;

    protected function setUp(): void
    {
        parent::setUp();

        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $this->comp = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $this->comp->styles()->attach($style->id);
        $brewer = Member::factory()->create(['email' => 'brewer@v.com']);
        $this->judge = Member::factory()->create(['email' => 'judge@v.com']);
        $this->comp->judges()->attach($this->judge->id);
        $this->entry = Entry::create([
            'competition_id' => $this->comp->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_uuid' => fake()->uuid(),
            'entry_id' => $this->entry->id,
            'aroma' => 8,
            'appearance' => 2,
            'flavour' => 14,
            'mouthfeel' => 4,
            'overall' => 7,
            'status' => 'draft',
        ], $overrides);
    }

    public function test_appearance_max_is_3(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['appearance' => 4]))
            ->assertStatus(422)->assertJsonValidationErrors(['appearance']);
    }

    public function test_flavour_max_is_20(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['flavour' => 21]))
            ->assertStatus(422)->assertJsonValidationErrors(['flavour']);
    }

    public function test_mouthfeel_max_is_5(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['mouthfeel' => 6]))
            ->assertStatus(422)->assertJsonValidationErrors(['mouthfeel']);
    }

    public function test_overall_max_is_10(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['overall' => 11]))
            ->assertStatus(422)->assertJsonValidationErrors(['overall']);
    }

    public function test_all_sections_accept_zero(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload([
            'aroma' => 0, 'appearance' => 0, 'flavour' => 0, 'mouthfeel' => 0, 'overall' => 0,
        ]))->assertOk();
    }

    public function test_all_sections_accept_max_values(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload([
            'aroma' => 12, 'appearance' => 3, 'flavour' => 20, 'mouthfeel' => 5, 'overall' => 10,
        ]))->assertOk();
    }
}
