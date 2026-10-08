<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionEntriesSortTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $competition;

    private Style $style;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'open']);
        $this->style = Style::create(['code' => '1A', 'category_name' => 'Cat', 'name' => 'Lager', 'version' => '2021', 'source' => 'custom']);
        $this->competition->styles()->attach($this->style);
    }

    private function entry(float $abv, int $sortOrder): Entry
    {
        return Entry::create([
            'competition_id' => $this->competition->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'abv' => $abv,
            'sort_order' => $sortOrder,
        ]);
    }

    private function sortAction(Testable $component): Testable
    {
        return $component->callAction(TestAction::make('sortByAbv')->schemaComponent('entriesActions'));
    }

    /** @return list<string|float|null> */
    private function abvOrder(Testable $component): array
    {
        return array_values(array_map(fn (array $row) => $row['abv'], $component->get('data.entries')));
    }

    public function test_sort_reorders_form_rows_with_unsaved_row_first_and_save_persists_sort_order(): void
    {
        $high = $this->entry(7.5, 1);
        $low = $this->entry(5.0, 2);
        $noAbv = $this->entry(5.0, 3);
        $noAbv->update(['abv' => null]);
        $newMember = Member::factory()->create();

        $component = Livewire::test(EditCompetition::class, ['record' => $this->competition->id])
            ->set('data.entries.record-new', [
                'member_id' => $newMember->id,
                'style_id' => $this->style->id,
                'abv' => '3.3',
                'brewer_notes' => null,
            ]);

        $this->sortAction($component)->assertHasNoActionErrors();

        $this->assertEquals([3.3, 5.0, 7.5, null], $this->abvOrder($component));
        $this->assertSame(0, Entry::where('member_id', $newMember->id)->count());
        $this->assertSame([1, 2, 3], Entry::orderBy('id')->pluck('sort_order')->all());

        $component->call('save')->assertHasNoFormErrors();

        $this->assertSame(1, Entry::where('member_id', $newMember->id)->firstOrFail()->sort_order);
        $this->assertSame(2, $low->fresh()->sort_order);
        $this->assertSame(3, $high->fresh()->sort_order);
        $this->assertSame(4, $noAbv->fresh()->sort_order);
    }

    public function test_sort_keeps_equal_abv_rows_in_their_current_order(): void
    {
        $first = $this->entry(5.0, 1);
        $second = $this->entry(5.0, 2);
        $this->entry(4.0, 3);

        $component = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);
        $this->sortAction($component);

        $this->assertSame(
            [4.0, 5.0, 5.0],
            array_map('floatval', $this->abvOrder($component)),
        );
        $keys = array_keys($component->get('data.entries'));
        $this->assertSame(
            ["record-{$first->id}", "record-{$second->id}"],
            array_slice($keys, 1),
        );
    }

    public function test_entry_registered_after_page_load_is_untouched_by_sort(): void
    {
        $this->entry(6.0, 1);
        $this->entry(4.0, 2);

        $component = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);

        $late = $this->entry(2.0, 3);

        $this->sortAction($component)->assertHasNoActionErrors();

        $this->assertSame(3, $late->fresh()->sort_order);
        $this->assertSame([1, 2, 3], Entry::orderBy('id')->pluck('sort_order')->all());
    }

    public function test_entry_labels_render_in_dutch(): void
    {
        $this->entry(5.0, 1);

        $this->actingAs(Member::factory()->admin()->create(['locale' => 'nl']))
            ->get(route('filament.admin.resources.competitions.edit', ['record' => $this->competition, 'tab' => 'entries::data::tab']))
            ->assertOk()
            ->assertSee('Sorteer op ABV')
            ->assertSee('Brouwer')
            ->assertSee('Stijl')
            ->assertSee('Notities brouwer')
            ->assertDontSee('Sort by ABV');
    }
}
