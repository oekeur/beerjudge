<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use App\Services\BottleNumberAssignmentService;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Once;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CompetitionEntriesLockTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Style $style;

    private Style $style2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
        $this->style = Style::create(['code' => '1A', 'category_name' => 'Cat', 'name' => 'Lager', 'version' => '2021', 'source' => 'custom']);
        $this->style2 = Style::create(['code' => '1B', 'category_name' => 'Cat', 'name' => 'Pils', 'version' => '2021', 'source' => 'custom']);
    }

    /** @return array<string, array{string}> */
    public static function lockedStatuses(): array
    {
        return ['judging' => ['judging'], 'closed' => ['closed']];
    }

    private function competition(string $status): Competition
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => $status]);
        $competition->styles()->attach($this->style);

        return $competition;
    }

    private function competitionWithTwoStyles(string $status): Competition
    {
        $competition = $this->competition($status);
        $competition->styles()->attach($this->style2);

        return $competition;
    }

    private function entry(Competition $competition, int $sortOrder, string $notes = 'orig'): Entry
    {
        return Entry::create([
            'competition_id' => $competition->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'abv' => 5.0,
            'brewer_notes' => $notes,
            'bottle_number' => $sortOrder,
            'sort_order' => $sortOrder,
        ]);
    }

    /** @return array<string, mixed> */
    private function newRow(): array
    {
        return [
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'abv' => '4.4',
            'brewer_notes' => null,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function snapshot(): array
    {
        return Entry::orderBy('id')->get(['id', 'member_id', 'style_id', 'abv', 'brewer_notes', 'bottle_number', 'sort_order'])->toArray();
    }

    #[DataProvider('lockedStatuses')]
    public function test_repeater_is_disabled_when_locked(string $status): void
    {
        $competition = $this->competition($status);
        $this->entry($competition, 1);

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertFormFieldIsDisabled('entries')
            ->assertSee(__('competition.entry.locked'));
    }

    #[DataProvider('lockedStatuses')]
    public function test_save_ignores_added_row(string $status): void
    {
        $competition = $this->competition($status);
        $this->entry($competition, 1);
        $before = $this->snapshot();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.entries.record-new', $this->newRow())
            ->call('save');

        $this->assertSame($before, $this->snapshot());
    }

    #[DataProvider('lockedStatuses')]
    public function test_save_ignores_removed_row(string $status): void
    {
        $competition = $this->competition($status);
        $keep = $this->entry($competition, 1);
        $drop = $this->entry($competition, 2);
        $before = $this->snapshot();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.entries', ['record-'.$keep->id => $this->rowOf($keep)])
            ->call('save');

        $this->assertSame($before, $this->snapshot());
        $this->assertNotNull(Entry::find($drop->id));
    }

    #[DataProvider('lockedStatuses')]
    public function test_save_ignores_edited_and_reordered_rows(string $status): void
    {
        $competition = $this->competition($status);
        $first = $this->entry($competition, 1);
        $second = $this->entry($competition, 2);
        $before = $this->snapshot();

        $edited = $this->rowOf($first);
        $edited['brewer_notes'] = 'tampered';

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.entries', [
                'record-'.$second->id => $this->rowOf($second),
                'record-'.$first->id => $edited,
            ])
            ->call('save');

        $this->assertSame($before, $this->snapshot());
    }

    /** @return array<string, mixed> */
    private function rowOf(Entry $entry): array
    {
        return [
            'member_id' => $entry->member_id,
            'style_id' => $entry->style_id,
            'abv' => $entry->abv,
            'brewer_notes' => $entry->brewer_notes,
        ];
    }

    #[DataProvider('lockedStatuses')]
    public function test_sort_by_abv_is_not_available_when_locked(string $status): void
    {
        $competition = $this->competition($status);

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertActionDoesNotExist(TestAction::make('sortByAbv')->schemaComponent('entriesActions'));
    }

    public function test_sort_by_abv_is_available_when_open_and_repeater_editable(): void
    {
        $competition = $this->competition('open');

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertActionVisible(TestAction::make('sortByAbv')->schemaComponent('entriesActions'))
            ->assertFormFieldIsEnabled('entries');
    }

    public function test_open_competition_still_saves_added_row(): void
    {
        $competition = $this->competition('open');

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.entries.record-new', $this->newRow())
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, Entry::count());
    }

    public function test_moving_to_judging_locks_the_open_page_without_reload(): void
    {
        $competition = $this->competition('open');
        $this->entry($competition, 1);

        $component = Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction('moveToJudging')
            ->assertFormFieldIsDisabled('entries');

        $before = $this->snapshot();

        $component->set('data.entries.record-new', $this->newRow())->call('save');

        $this->assertSame($before, $this->snapshot());
        $this->assertSame('judging', $competition->fresh()->status->value);
    }

    public function test_reopen_keeps_the_lock(): void
    {
        $competition = $this->competition('closed');
        $this->entry($competition, 1);

        Livewire::test(EditCompetition::class, ['record' => $competition->id])->callAction('reopenJudging');
        $this->assertSame('judging', $competition->fresh()->status->value);

        Once::flush();
        $before = $this->snapshot();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertFormFieldIsDisabled('entries')
            ->set('data.entries.record-new', $this->newRow())
            ->call('save');

        $this->assertSame($before, $this->snapshot());
    }

    /** Stale tab: mounted in Open, another tab moves to Judging, stale tab saves with removed + added row. */
    public function test_stale_open_tab_save_after_other_tab_moves_to_judging(): void
    {
        $c = $this->competitionWithTwoStyles('open');
        $keep = $this->entry($c, 1);
        $drop = $this->entry($c, 2);

        $page = Livewire::test(EditCompetition::class, ['record' => $c->id]);

        $other = Competition::find($c->id);
        app(BottleNumberAssignmentService::class)->assign($other);
        $other->transitionTo(CompetitionStatus::Judging);
        Once::flush();
        $before = $this->snapshot();

        $page->set('data.entries', [
            'record-'.$keep->id => ['member_id' => $keep->member_id, 'style_id' => $keep->style_id, 'abv' => '9.9', 'brewer_notes' => 'x'],
            'new' => ['member_id' => Member::factory()->create()->id, 'style_id' => $this->style->id, 'abv' => '4', 'brewer_notes' => null],
        ])->set('data.name', 'Renamed')->call('save');

        $this->assertSame($before, $this->snapshot());
        $this->assertSame('Renamed', $c->fresh()->name);
        $this->assertSame('judging', $c->fresh()->status->value);
    }

    /** In-page: unsaved added row, then Move to Judging, then Save. Does the form still show the phantom row? */
    public function test_unsaved_row_before_move_to_judging_is_shown_after_save(): void
    {
        $c = $this->competitionWithTwoStyles('open');
        $this->entry($c, 1);

        $m = Member::factory()->create();
        $page = Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.entries.new', ['member_id' => $m->id, 'style_id' => $this->style->id, 'abv' => '4', 'brewer_notes' => null])
            ->callAction('moveToJudging');
        Once::flush();
        $page->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(1, Entry::count());
        $keys = array_keys($page->get('data.entries'));
        $this->assertCount(1, $keys, 'phantom row still shown in locked repeater after Saved notification');
    }

    /** Locked: other fields still save; style used by an entry cannot be removed; unused style can. */
    public function test_locked_other_fields_and_styles(): void
    {
        $c = $this->competitionWithTwoStyles('judging');
        $this->entry($c, 1);

        Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.name', 'New')
            ->set('data.styles', [$this->style2->id])
            ->call('save')
            ->assertHasFormErrors(['styles']);
        $this->assertTrue($c->fresh()->styles->contains($this->style));

        Once::flush();
        Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.name', 'New')
            ->set('data.styles', [$this->style->id])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('New', $c->fresh()->name);
        $this->assertSame([$this->style->id], $c->fresh()->styles->pluck('id')->all());
    }

    /** Locked save with an entry whose member is now inactive and abv null does not trip disabled-child validation. */
    public function test_locked_save_with_inactive_member_entry(): void
    {
        $c = $this->competitionWithTwoStyles('judging');
        $e = $this->entry($c, 1);
        $e->member->forceFill(['is_active' => false])->save();
        $e->forceFill(['abv' => null, 'brewer_notes' => null])->saveQuietly();

        Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.name', 'New')
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('New', $c->fresh()->name);
    }

    /** Admin clicked Add (empty row), then Move to Judging, then edits the name and saves. */
    public function test_empty_unsaved_row_before_move_to_judging_blocks_save(): void
    {
        $c = $this->competitionWithTwoStyles('open');
        $this->entry($c, 1);

        $page = Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.entries.new', ['member_id' => null, 'style_id' => null, 'abv' => null, 'brewer_notes' => null])
            ->callAction('moveToJudging')
            ->assertFormFieldIsDisabled('entries');
        Once::flush();
        $page->set('data.judging_date', '2026-11-01')->call('save');
        $this->assertSame('2026-11-01', $c->fresh()->judging_date?->format('Y-m-d'));
    }

    /** Stale tab variant of the same: mounted Open with empty added row, other tab moves to Judging. */
    public function test_stale_tab_empty_row_blocks_save(): void
    {
        $c = $this->competitionWithTwoStyles('open');
        $this->entry($c, 1);
        $page = Livewire::test(EditCompetition::class, ['record' => $c->id])
            ->set('data.entries.new', ['member_id' => null, 'style_id' => null, 'abv' => null, 'brewer_notes' => null]);
        Competition::find($c->id)->transitionTo(CompetitionStatus::Judging);
        Once::flush();
        $page->set('data.name', 'Renamed')->call('save');
        $this->assertSame('Renamed', $c->fresh()->name);
    }
}
