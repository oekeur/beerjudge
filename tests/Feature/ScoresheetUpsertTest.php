<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScoresheetUpsertTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeJudgingCompetition(): Competition
    {
        return Competition::create([
            'name' => 'Test Cup',
            'year' => 2025,
            'status' => 'judging',
        ]);
    }

    private function makeStyle(): Style
    {
        return Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
    }

    private function makeMember(array $attrs = []): Member
    {
        return Member::factory()->create($attrs);
    }

    private function makeEntry(Competition $comp, Member $brewer, Style $style): Entry
    {
        $comp->styles()->syncWithoutDetaching([$style->id]);

        return Entry::create([
            'competition_id' => $comp->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
        ]);
    }

    private function payload(int $entryId, string $uuid, array $overrides = []): array
    {
        return array_merge([
            'client_uuid' => $uuid,
            'entry_id' => $entryId,
            'aroma' => 8,
            'appearance' => 2,
            'flavour' => 14,
            'mouthfeel' => 4,
            'overall' => 7,
            'status' => 'draft',
        ], $overrides);
    }

    public function test_judge_can_submit_a_scoresheet(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'brewer@test.com']);
        $judge = $this->makeMember(['email' => 'judge@test.com']);
        $entry = $this->makeEntry($comp, $brewer, $style);
        $uuid = fake()->uuid();

        $response = $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, $uuid));

        $response->assertOk()->assertJsonStructure(['id', 'client_uuid', 'total', 'status', 'saved_at']);
        $this->assertEquals(35, $response->json('total')); // 8+2+14+4+7
        $this->assertDatabaseHas('scoresheets', ['client_uuid' => $uuid, 'judge_id' => $judge->id]);
    }

    public function test_upsert_is_idempotent_by_client_uuid(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'brewer2@test.com']);
        $judge = $this->makeMember(['email' => 'judge2@test.com']);
        $entry = $this->makeEntry($comp, $brewer, $style);
        $uuid = fake()->uuid();

        $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, $uuid));
        $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, $uuid, ['aroma' => 10]));

        $this->assertEquals(1, Scoresheet::where('client_uuid', $uuid)->count());
        $this->assertEquals(10, Scoresheet::where('client_uuid', $uuid)->value('aroma'));
    }

    public function test_judge_cannot_score_own_entry(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $member = $this->makeMember(['email' => 'self@test.com']);
        $entry = $this->makeEntry($comp, $member, $style);

        $response = $this->actingAs($member)->postJson('/scoresheets', $this->payload($entry->id, fake()->uuid()));

        $response->assertStatus(422);
    }

    public function test_scoresheet_rejected_when_competition_not_in_judging(): void
    {
        $comp = Competition::create(['name' => 'Draft Cup', 'year' => 2025, 'status' => 'draft']);
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'b3@test.com']);
        $judge = $this->makeMember(['email' => 'j3@test.com']);
        $entry = $this->makeEntry($comp, $brewer, $style);

        $response = $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, fake()->uuid()));

        $response->assertStatus(422);
    }

    public function test_aroma_max_validation(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'b4@test.com']);
        $judge = $this->makeMember(['email' => 'j4@test.com']);
        $entry = $this->makeEntry($comp, $brewer, $style);

        $response = $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, fake()->uuid(), ['aroma' => 13]));

        $response->assertStatus(422)->assertJsonValidationErrors(['aroma']);
    }

    public function test_submit_sets_status_and_total(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'b5@test.com']);
        $judge = $this->makeMember(['email' => 'j5@test.com']);
        $entry = $this->makeEntry($comp, $brewer, $style);
        $uuid = fake()->uuid();

        $response = $this->actingAs($judge)->postJson('/scoresheets', $this->payload($entry->id, $uuid, ['status' => 'submitted']));

        $response->assertOk();
        $sheet = Scoresheet::where('client_uuid', $uuid)->first();
        $this->assertEquals('submitted', $sheet->status);
        $this->assertNotNull($sheet->submitted_at);
        $this->assertEquals(35, $sheet->total);
    }

    public function test_unauthenticated_request_redirects(): void
    {
        $response = $this->postJson('/scoresheets', ['client_uuid' => fake()->uuid()]);
        $response->assertStatus(401);
    }

    public function test_judge_cannot_use_another_judges_uuid(): void
    {
        $comp = $this->makeJudgingCompetition();
        $style = $this->makeStyle();
        $brewer = $this->makeMember(['email' => 'brewer_x@test.com']);
        $judgeA = $this->makeMember(['email' => 'judge_a@test.com']);
        $judgeB = $this->makeMember(['email' => 'judge_b@test.com']);
        $entryA = $this->makeEntry($comp, $brewer, $style);
        $entryB = $this->makeEntry($comp, $judgeA, $style); // brewed by judgeA so judgeB can judge it

        $sharedUuid = fake()->uuid();

        // Judge A creates a sheet with this UUID
        $this->actingAs($judgeA)->postJson('/scoresheets', $this->payload($entryA->id, $sharedUuid, ['aroma' => 10]))
            ->assertOk();

        // Judge B POSTs with the same UUID — must be rejected
        $this->actingAs($judgeB)->postJson('/scoresheets', $this->payload($entryB->id, $sharedUuid, ['aroma' => 3]))
            ->assertStatus(422);

        // Judge A's sheet is unchanged
        $this->assertEquals(10, Scoresheet::where('client_uuid', $sharedUuid)->value('aroma'));
    }
}
