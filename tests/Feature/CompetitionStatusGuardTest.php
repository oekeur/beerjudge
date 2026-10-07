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
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
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
        return Competition::create(['name' => 'Test Cup', 'year' => 2026, 'status' => $status]);
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
}
