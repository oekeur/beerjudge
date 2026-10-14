<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ScoresheetRevisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Member $judge;

    private Entry $entry;

    private string $uuid;

    protected function setUp(): void
    {
        parent::setUp();

        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $this->judge = Member::factory()->create();
        $competition->judges()->attach($this->judge->id);
        $this->entry = Entry::create(['competition_id' => $competition->id, 'member_id' => $brewer->id, 'style_id' => $style->id]);
        $this->uuid = fake()->uuid();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_uuid' => $this->uuid,
            'entry_id' => $this->entry->id,
            'aroma' => 8, 'appearance' => 2, 'flavour' => 14, 'mouthfeel' => 4, 'overall' => 7,
            'status' => 'submitted',
            'client_revision' => 5,
        ], $overrides);
    }

    public function test_newer_revision_applies(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => 1, 'status' => 'draft']))->assertOk();

        $response = $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => 2]));

        $response->assertOk()->assertJson(['status' => 'submitted', 'client_revision' => 2, 'ignored' => false]);
        $this->assertDatabaseHas('scoresheets', ['client_uuid' => $this->uuid, 'status' => 'submitted', 'client_revision' => 2]);
    }

    public function test_older_revision_is_ignored_and_the_stored_sheet_is_returned(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload())->assertOk();

        $response = $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload([
            'client_revision' => 3, 'status' => 'draft', 'aroma' => 9, 'appearance' => null, 'flavour' => null, 'mouthfeel' => null, 'overall' => null,
        ]));

        $response->assertOk()->assertJson([
            'status' => 'submitted',
            'client_revision' => 5,
            'total' => 35,
            'ignored' => true,
            'sheet' => [
                'entry_id' => $this->entry->id,
                'client_uuid' => $this->uuid,
                'client_revision' => 5,
                'aroma' => 8, 'appearance' => 2, 'flavour' => 14, 'mouthfeel' => 4, 'overall' => 7,
                'descriptors' => [],
                'status' => 'submitted',
            ],
        ]);
        $sheet = Scoresheet::where('client_uuid', $this->uuid)->sole();
        $this->assertSame('submitted', $sheet->status->value);
        $this->assertSame(8, $sheet->aroma);
        $this->assertSame(5, $sheet->client_revision);
    }

    public function test_equal_revision_is_ignored(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload())->assertOk();

        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['aroma' => 1]))
            ->assertOk()->assertJson(['status' => 'submitted']);

        $this->assertSame(8, Scoresheet::where('client_uuid', $this->uuid)->sole()->aroma);
    }

    public function test_payload_without_revision_still_updates(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload())->assertOk();

        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => null, 'aroma' => 1, 'status' => 'draft']))->assertOk();

        $sheet = Scoresheet::where('client_uuid', $this->uuid)->sole();
        $this->assertSame(1, $sheet->aroma);
        $this->assertSame(5, $sheet->client_revision);
    }

    public function test_negative_revision_is_rejected(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => -1]))
            ->assertUnprocessable()->assertJsonValidationErrors('client_revision');
    }

    public function test_boolean_and_oversized_revisions_are_rejected(): void
    {
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => true]))
            ->assertUnprocessable()->assertJsonValidationErrors('client_revision');
        $this->actingAs($this->judge)->postJson('/scoresheets', $this->payload(['client_revision' => 2147483648]))
            ->assertUnprocessable()->assertJsonValidationErrors('client_revision');
    }
}
