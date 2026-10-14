<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Pages\CreateCompetition;
use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionJudgeListTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $competition;

    private Member $judgeA;

    private Member $judgeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
        $this->judgeA = Member::factory()->create();
        $this->judgeB = Member::factory()->create();
        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'open']);
    }

    /** @return list<int> */
    private function judgeIds(): array
    {
        return $this->competition->judges()->pluck('members.id')->sort()->values()->all();
    }

    public function test_judge_registered_while_page_is_open_survives_save(): void
    {
        $this->competition->judges()->attach($this->judgeA->id);

        $page = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);

        $this->competition->judges()->attach($this->judgeB->id);

        $page->call('save')->assertHasNoFormErrors();

        $this->assertSame([$this->judgeA->id, $this->judgeB->id], $this->judgeIds());
    }

    public function test_removal_on_one_page_is_not_undone_by_save_from_stale_page(): void
    {
        $this->competition->judges()->attach([$this->judgeA->id, $this->judgeB->id]);

        $first = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);
        $second = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);

        $first->set('data.judges', [$this->judgeA->id]);
        $this->assertSame([$this->judgeA->id], $this->judgeIds());

        $second->call('save')->assertHasNoFormErrors();

        $this->assertSame([$this->judgeA->id], $this->judgeIds());
    }

    public function test_save_without_judge_change_leaves_judges_unchanged(): void
    {
        $this->competition->judges()->attach($this->judgeA->id);

        Livewire::test(EditCompetition::class, ['record' => $this->competition->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame([$this->judgeA->id], $this->judgeIds());
    }

    public function test_adding_and_removing_a_judge_persists_without_save(): void
    {
        $this->competition->judges()->attach($this->judgeA->id);

        Livewire::test(EditCompetition::class, ['record' => $this->competition->id])
            ->set('data.judges', [$this->judgeA->id, $this->judgeB->id]);

        $this->assertSame([$this->judgeA->id, $this->judgeB->id], $this->judgeIds());

        Livewire::test(EditCompetition::class, ['record' => $this->competition->id])
            ->assertSet('data.judges', [$this->judgeA->id, $this->judgeB->id])
            ->set('data.judges', [$this->judgeB->id]);

        $this->assertSame([$this->judgeB->id], $this->judgeIds());
    }

    public function test_judges_field_is_hidden_on_create_and_shown_on_edit(): void
    {
        Livewire::test(CreateCompetition::class)->assertFormFieldIsHidden('judges');
        Livewire::test(EditCompetition::class, ['record' => $this->competition->id])->assertFormFieldIsVisible('judges');
    }
}
