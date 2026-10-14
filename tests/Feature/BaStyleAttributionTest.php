<?php

namespace Tests\Feature;

use App\Filament\Forms\Components\StyleMultiSelect;
use App\Filament\Resources\Styles\Pages\EditStyle;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BaStyleAttributionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function style(string $source, string $code): Style
    {
        return Style::create([
            'source' => $source, 'code' => $code, 'category_name' => 'Cat', 'name' => "Style {$code}",
            'version' => $source === 'ba_beer' ? '2026' : '2021',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function judgingEntriesFor(Style $style): array
    {
        $comp = Competition::forceCreate(['name' => 'Cup', 'year' => 2026, 'status' => 'judging']);
        $comp->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $judge = Member::factory()->create();
        $comp->judges()->attach($judge->id);
        Entry::create(['competition_id' => $comp->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);

        $entries = [];
        $this->actingAs($judge)->get("/judging/{$comp->id}")->assertOk()
            ->assertViewHas('serverData', function (array $data) use (&$entries): bool {
                $entries = $data['entries']->all();

                return true;
            });

        return $entries;
    }

    public function test_judging_payload_carries_the_ba_attribution_for_a_ba_entry(): void
    {
        $entries = $this->judgingEntriesFor($this->style('ba_beer', 'BA-001'));

        $this->assertSame('ba_beer', $entries[0]['style_source']);
        $this->assertSame(
            'Based on Brewers Association 2026 Beer Style Guidelines with changes. Used with permission of Brewers Association.',
            $entries[0]['style_attribution']
        );
    }

    public function test_judging_payload_has_no_attribution_for_a_bjcp_entry(): void
    {
        $entries = $this->judgingEntriesFor($this->style('bjcp_beer', '1A'));

        $this->assertSame('bjcp_beer', $entries[0]['style_source']);
        $this->assertNull($entries[0]['style_attribution']);
    }

    public function test_style_edit_page_shows_attribution_for_a_ba_style_only(): void
    {
        $this->actingAs(Member::factory()->admin()->create());
        $ba = $this->style('ba_beer', 'BA-001');
        $bjcp = $this->style('bjcp_beer', '1A');

        Livewire::test(EditStyle::class, ['record' => $ba->id])
            ->assertSee(Style::SOURCE_ATTRIBUTIONS['ba_beer']);

        Livewire::test(EditStyle::class, ['record' => $bjcp->id])
            ->assertDontSee(Style::SOURCE_ATTRIBUTIONS['ba_beer']);
    }

    public function test_style_multi_select_lists_bjcp_beer_before_brewers_association(): void
    {
        $this->style('ba_beer', 'BA-001');
        $this->style('bjcp_beer', '1A');
        $this->style('custom', 'X1');

        $groups = array_keys((new StyleMultiSelect('styles'))->getCategorizedOptions());

        $this->assertSame(['bjcp_beer', 'ba_beer', 'custom'], $groups);
    }
}
