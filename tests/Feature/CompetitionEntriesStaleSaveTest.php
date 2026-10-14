<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionEntriesStaleSaveTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $competition;

    private Style $style;

    /** @var list<Member> */
    private array $members = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'open']);
        $this->style = Style::create(['code' => '1A', 'category_name' => 'Cat', 'name' => 'Lager', 'version' => '2021', 'source' => 'custom']);
        $this->competition->styles()->attach($this->style);
        $this->members = Member::factory()->count(5)->create()->all();
    }

    private function entry(int $memberIndex, ?float $abv = 5.0): Entry
    {
        return Entry::create([
            'competition_id' => $this->competition->id,
            'member_id' => $this->members[$memberIndex]->id,
            'style_id' => $this->style->id,
            'abv' => $abv,
        ]);
    }

    private function page(): Testable
    {
        return Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);
    }

    /** @return list<int> entry ids in tasting order */
    private function order(): array
    {
        return Entry::where('competition_id', $this->competition->id)->orderBy('sort_order')->pluck('id')->all();
    }

    public function test_late_entry_survives_save_and_goes_after_page_rows(): void
    {
        $a = $this->entry(0);
        $b = $this->entry(1);
        $page = $this->page();
        $late = $this->entry(2);

        $page->call('save')->assertHasNoFormErrors();

        $this->assertNotNull($late->fresh());
        $this->assertSame([$a->id, $b->id, $late->id], $this->order());
        $this->assertSame([1, 2, 3], Entry::orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_removed_row_is_still_deleted_while_late_entry_is_kept(): void
    {
        $a = $this->entry(0);
        $b = $this->entry(1);
        $page = $this->page();
        $late = $this->entry(2);

        $this->removeRow($page, $a);
        $page->call('save')->assertHasNoFormErrors();

        $this->assertNull($a->fresh());
        $this->assertSame([$b->id, $late->id], $this->order());
        $this->assertSame([1, 2], Entry::orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_added_row_and_edited_fields_save(): void
    {
        $a = $this->entry(0);
        $page = $this->page();

        $page->set("data.entries.record-{$a->id}.brewer_notes", 'Dry hopped')
            ->set('data.entries.record-new', [
                'member_id' => $this->members[3]->id,
                'style_id' => $this->style->id,
                'abv' => '4.5',
                'brewer_notes' => null,
            ])
            ->call('save')->assertHasNoFormErrors();

        $this->assertSame('Dry hopped', $a->fresh()->brewer_notes);
        $added = Entry::where('member_id', $this->members[3]->id)->firstOrFail();
        $this->assertSame([$a->id, $added->id], $this->order());
    }

    public function test_second_save_without_changes_deletes_and_duplicates_nothing(): void
    {
        $this->entry(0);
        $this->entry(1);
        $page = $this->page();

        $page->call('save')->call('save')->assertHasNoFormErrors();

        $this->assertSame(2, Entry::count());
        $this->assertSame([1, 2], Entry::orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_row_added_saved_then_removed_and_saved_is_deleted(): void
    {
        $a = $this->entry(0);
        $page = $this->page();

        $page->set('data.entries.record-new', [
            'member_id' => $this->members[3]->id,
            'style_id' => $this->style->id,
            'abv' => '4.5',
            'brewer_notes' => null,
        ])->call('save')->assertHasNoFormErrors();

        $added = Entry::where('member_id', $this->members[3]->id)->firstOrFail();
        $this->removeRow($page, $added);
        $page->call('save')->assertHasNoFormErrors();

        $this->assertNull($added->fresh());
        $this->assertNotNull($a->fresh());
    }

    public function test_late_entry_survives_two_consecutive_saves_and_stays_after_page_rows(): void
    {
        $a = $this->entry(0);
        $b = $this->entry(1);
        $page = $this->page();
        $late = $this->entry(2);

        $page->call('save')->call('save')->assertHasNoFormErrors();

        $this->assertSame([$a->id, $b->id, $late->id], $this->order());
        $this->assertSame([1, 2, 3], Entry::orderBy('sort_order')->pluck('sort_order')->all());
    }

    public function test_several_late_entries_keep_registration_order(): void
    {
        $a = $this->entry(0);
        $page = $this->page();
        $late1 = $this->entry(2);
        $late2 = $this->entry(3);

        $page->call('save')->call('save')->assertHasNoFormErrors();

        $this->assertSame([$a->id, $late1->id, $late2->id], $this->order());
    }

    private function otherStyle(): Style
    {
        $other = Style::create(['code' => '2A', 'category_name' => 'Cat', 'name' => 'Pils', 'version' => '2021', 'source' => 'custom']);
        $this->competition->styles()->attach($other);

        return $other;
    }

    public function test_style_used_by_a_late_entry_cannot_be_removed(): void
    {
        $other = $this->otherStyle();
        $this->entry(0);
        $page = $this->page();
        $late = Entry::create(['competition_id' => $this->competition->id, 'member_id' => $this->members[2]->id, 'style_id' => $other->id, 'abv' => 5.0]);
        $lateOrder = $late->sort_order;

        $page->set('data.styles', [(string) $this->style->id])->call('save')->assertHasFormErrors(['styles']);

        $this->assertEqualsCanonicalizing([$this->style->id, $other->id], $this->competition->styles()->pluck('styles.id')->all());
        $this->assertSame($lateOrder, $late->fresh()->sort_order);
        $this->assertSame($other->id, $late->fresh()->style_id);
    }

    public function test_style_used_by_a_loaded_entry_cannot_be_removed(): void
    {
        $other = $this->otherStyle();
        Entry::create(['competition_id' => $this->competition->id, 'member_id' => $this->members[0]->id, 'style_id' => $other->id, 'abv' => 5.0]);
        $page = $this->page();

        $page->set('data.styles', [(string) $this->style->id])->call('save')->assertHasFormErrors(['styles']);

        $this->assertSame(2, $this->competition->styles()->count());
    }

    public function test_unused_style_can_still_be_removed(): void
    {
        $other = $this->otherStyle();
        $this->entry(0);
        $page = $this->page();

        $page->set('data.styles', [(string) $this->style->id])->call('save')->assertHasNoFormErrors();

        $this->assertSame([$this->style->id], $this->competition->styles()->pluck('styles.id')->all());
    }

    private function removeRow(Testable $page, Entry $entry): void
    {
        $entries = $page->get('data.entries');
        unset($entries["record-{$entry->id}"]);
        $page->set('data.entries', $entries);
    }
}
