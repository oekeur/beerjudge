<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Filament\Resources\Competitions\CompetitionResource;
use App\Filament\Resources\Competitions\Pages\CreateCompetition;
use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use App\Notifications\CompetitionResultsNotification;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionStatusGuardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
    }

    private function competition(string $status): Competition
    {
        return Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => $status]);
    }

    public function test_status_field_is_disabled_on_edit(): void
    {
        $competition = $this->competition('open');

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertFormFieldIsDisabled('status');
    }

    public function test_tampered_status_on_edit_does_not_change_status(): void
    {
        $competition = $this->competition('open');

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.status', 'closed')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(CompetitionStatus::Open, $competition->fresh()->status);
        $this->assertNull($competition->fresh()->results_mailed_at);
    }

    public function test_create_defaults_to_draft_even_with_tampered_status(): void
    {
        Livewire::test(CreateCompetition::class)
            ->assertFormSet(['status' => CompetitionStatus::Draft])
            ->set('data.name', 'New Cup')
            ->set('data.year', 2026)
            ->set('data.status', 'judging')
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(CompetitionStatus::Draft, Competition::where('name', 'New Cup')->firstOrFail()->status);
    }

    public function test_reopen_judging_moves_closed_competition_back_to_judging(): void
    {
        Notification::fake();
        $competition = $this->competition('closed');
        $mailedAt = now()->subDay()->startOfSecond();
        $competition->forceFill(['results_mailed_at' => $mailedAt])->save();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction(TestAction::make('reopenJudging'))
            ->assertHasNoActionErrors()
            ->assertRedirect(CompetitionResource::getUrl('edit', ['record' => $competition]));

        $fresh = $competition->fresh();
        $this->assertSame(CompetitionStatus::Judging, $fresh->status);
        $this->assertTrue($fresh->results_mailed_at->equalTo($mailedAt));
        Notification::assertNothingSent();
    }

    public function test_reopen_then_close_does_not_mail_again(): void
    {
        Notification::fake();
        $competition = $this->competition('closed');
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
            'bottle_number' => 1,
        ]);
        $competition->forceFill(['results_mailed_at' => now()->subDay()])->save();

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction(TestAction::make('reopenJudging'));

        $this->assertSame(CompetitionStatus::Judging, $competition->fresh()->status);

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->callAction(TestAction::make('moveToClosed'));

        $this->assertSame(CompetitionStatus::Closed, $competition->fresh()->status);
        Notification::assertNotSentTo($brewer, CompetitionResultsNotification::class);
    }

    public function test_reopen_action_only_exists_on_closed_competitions(): void
    {
        foreach (['draft', 'open', 'judging'] as $status) {
            $competition = $this->competition($status);

            Livewire::test(EditCompetition::class, ['record' => $competition->id])
                ->assertActionDoesNotExist('reopenJudging');
        }
    }

    public function test_judging_competition_has_no_action_back_to_open(): void
    {
        $competition = $this->competition('judging');

        Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->assertActionDoesNotExist('moveToOpen')
            ->assertActionExists('moveToClosed');
    }

    public function test_reopen_description_warns_that_members_cannot_see_results(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'closed']);

        foreach (['en' => 'Members cannot see results until the competition is closed again.', 'nl' => 'Leden kunnen de resultaten pas weer zien als de wedstrijd opnieuw gesloten is.'] as $locale => $sentence) {
            app()->setLocale($locale);

            $this->assertStringContainsString($sentence, __('competition.action.reopen_judging_description'));

            $component = Livewire::test(EditCompetition::class, ['record' => $competition->id])
                ->mountAction(TestAction::make('reopenJudging'));

            $this->assertStringContainsString($sentence, (string) $component->instance()->getMountedAction()->getModalDescription());
        }
    }

    public function test_forward_actions_rebuild_the_header_in_the_same_component(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'draft']);
        $component = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        $component->callAction(TestAction::make('moveToOpen'))->assertNoRedirect();
        $this->assertSame(CompetitionStatus::Open, $competition->fresh()->status);
        $this->assertSame(['moveToJudging'], $this->lifecycleActionNames($component));

        $component->callAction(TestAction::make('moveToJudging'))->assertNoRedirect();
        $this->assertSame(CompetitionStatus::Judging, $competition->fresh()->status);
        $this->assertSame(['moveToClosed'], $this->lifecycleActionNames($component));

        Notification::fake();
        $component->callAction(TestAction::make('moveToClosed'))->assertNoRedirect();
        $this->assertSame(CompetitionStatus::Closed, $competition->fresh()->status);
        $this->assertSame(['reopenJudging'], $this->lifecycleActionNames($component));
    }

    /**
     * @return list<string>
     */
    private function lifecycleActionNames(Testable $component): array
    {
        $names = collect($component->instance()->getCachedHeaderActions())
            ->map(fn ($action) => $action->getName())
            ->intersect(['moveToOpen', 'moveToJudging', 'moveToClosed', 'reopenJudging']);

        return $names->values()->all();
    }

    public function test_unsaved_form_edits_stay_on_screen_and_are_not_saved_by_any_transition(): void
    {
        $competition = Competition::forceCreate(['name' => 'Saved', 'year' => 2026, 'status' => 'draft']);
        Notification::fake();

        $component = Livewire::test(EditCompetition::class, ['record' => $competition->id])
            ->set('data.name', 'Unsaved');

        foreach (['moveToOpen' => CompetitionStatus::Open, 'moveToJudging' => CompetitionStatus::Judging, 'moveToClosed' => CompetitionStatus::Closed] as $action => $status) {
            $component->callAction(TestAction::make($action))
                ->assertNoRedirect()
                ->assertSet('data.name', 'Unsaved');

            $fresh = $competition->fresh();
            $this->assertSame('Saved', $fresh->name);
            $this->assertSame($status, $fresh->status);
        }
    }

    /**
     * @return array{Competition, Style}
     */
    private function competitionWithStyle(string $status): array
    {
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => $status]);
        $competition->styles()->attach($style->id);

        return [$competition, $style];
    }

    public function test_entry_registered_after_the_page_loaded_survives_move_to_judging(): void
    {
        [$competition, $style] = $this->competitionWithStyle('open');
        Entry::create(['competition_id' => $competition->id, 'member_id' => Member::factory()->create()->id, 'style_id' => $style->id]);

        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        Entry::create(['competition_id' => $competition->id, 'member_id' => Member::factory()->create()->id, 'style_id' => $style->id]);

        $page->callAction(TestAction::make('moveToJudging'))->assertNoRedirect();

        $this->assertSame([1, 2], $competition->entries()->orderBy('id')->pluck('bottle_number')->all());
    }

    public function test_judge_joined_after_the_page_loaded_survives_move_to_closed(): void
    {
        [$competition] = $this->competitionWithStyle('judging');
        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        $competition->judges()->syncWithoutDetaching([Member::factory()->create()->id]);

        Notification::fake();
        $page->callAction(TestAction::make('moveToClosed'))->assertNoRedirect();

        $this->assertSame(1, $competition->judges()->count());
    }

    public function test_member_entry_edit_after_the_page_loaded_survives_move_to_judging(): void
    {
        [$competition, $style] = $this->competitionWithStyle('open');
        $entry = Entry::create([
            'competition_id' => $competition->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $style->id,
            'brewer_notes' => 'old',
        ]);
        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        Entry::whereKey($entry->id)->update(['brewer_notes' => 'new']);

        $page->callAction(TestAction::make('moveToJudging'))->assertNoRedirect();

        $this->assertSame('new', $entry->fresh()->brewer_notes);
    }

    public function test_entry_registered_after_the_page_loaded_survives_move_to_open(): void
    {
        [$competition, $style] = $this->competitionWithStyle('draft');
        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        Entry::create(['competition_id' => $competition->id, 'member_id' => Member::factory()->create()->id, 'style_id' => $style->id]);

        $page->callAction(TestAction::make('moveToOpen'))->assertNoRedirect();

        $this->assertSame(1, $competition->entries()->count());
    }

    public function test_entry_registered_after_the_page_loaded_survives_move_to_closed(): void
    {
        [$competition, $style] = $this->competitionWithStyle('judging');
        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        Entry::create(['competition_id' => $competition->id, 'member_id' => Member::factory()->create()->id, 'style_id' => $style->id, 'bottle_number' => 1]);

        Notification::fake();
        $page->callAction(TestAction::make('moveToClosed'))->assertNoRedirect();

        $this->assertSame(1, $competition->entries()->count());
    }

    public function test_judge_joined_after_the_page_loaded_survives_move_to_judging(): void
    {
        [$competition] = $this->competitionWithStyle('open');
        $page = Livewire::test(EditCompetition::class, ['record' => $competition->id]);

        $competition->judges()->syncWithoutDetaching([Member::factory()->create()->id]);

        $page->callAction(TestAction::make('moveToJudging'))->assertNoRedirect();

        $this->assertSame(1, $competition->judges()->count());
    }

    public function test_status_cannot_be_mass_assigned_on_create(): void
    {
        $this->expectException(MassAssignmentException::class);

        Competition::create(['name' => 'Sneaky Cup', 'year' => 2026, 'status' => 'closed']);
    }

    public function test_status_cannot_be_mass_assigned_on_update(): void
    {
        $competition = $this->competition('open');

        try {
            $competition->update(['status' => 'closed']);
            $this->fail('Expected MassAssignmentException.');
        } catch (MassAssignmentException) {
            $this->assertSame(CompetitionStatus::Open, $competition->fresh()->status);
        }
    }

    public function test_transition_to_sets_the_status(): void
    {
        $competition = $this->competition('open');

        $competition->transitionTo(CompetitionStatus::Judging);

        $this->assertSame(CompetitionStatus::Judging, $competition->fresh()->status);
    }
}
