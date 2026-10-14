<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\CompetitionResource;
use App\Filament\Resources\Competitions\Widgets\ResultsSummaryWidget;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResultsSummaryWidgetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_widget_polls_every_five_seconds(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);

        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(ResultsSummaryWidget::class, ['competitionId' => $competition->id])
            ->assertSeeHtml('wire:poll.5s');
    }

    public function test_dutch_member_sees_dutch_tile_labels_on_competition_page(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $this->actingAs(Member::factory()->admin()->create(['locale' => 'nl']));

        $this->get(CompetitionResource::getUrl('edit', ['record' => $competition]))
            ->assertSee('Inzendingen')
            ->assertSee('Rechters')
            ->assertSee('Voortgang')
            ->assertDontSee('Progress');
    }

    public function test_summary_poll_after_competition_deleted(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $this->actingAs(Member::factory()->admin()->create());

        $component = Livewire::test(ResultsSummaryWidget::class, ['competitionId' => $competition->id]);
        $competition->delete();

        $component->call('$refresh')
            ->assertSee('No longer available')
            ->assertDontSeeHtml('wire:poll');
    }

    public function test_summary_counts_a_judge_who_left_and_never_exceeds_the_eligible_count(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create(['name' => 'Brewer Bob']);
        $stayer = Member::factory()->create(['name' => 'Stayer Sue']);
        $leaver = Member::factory()->create(['name' => 'Leaver Lou']);
        $entry = Entry::create(['competition_id' => $competition->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        $competition->judges()->attach($stayer->id);

        foreach ([$stayer, $leaver] as $judge) {
            Scoresheet::create([
                'entry_id' => $entry->id, 'judge_id' => $judge->id, 'aroma' => 8, 'appearance' => 2, 'flavour' => 14,
                'mouthfeel' => 4, 'overall' => 7, 'client_uuid' => fake()->uuid(), 'status' => 'submitted',
            ]);
        }

        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(ResultsSummaryWidget::class, ['competitionId' => $competition->id])
            ->assertSeeInOrder(['2 / 2', '100%'])
            ->assertSee('2/2')
            ->assertDontSee('Leaver Lou')
            ->assertDontSee('2 / 1')
            ->assertDontSee('200%');
    }

    public function test_summary_lists_only_current_judges_as_missing(): void
    {
        $competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $waiting = Member::factory()->create(['name' => 'Waiting Wim']);
        Entry::create(['competition_id' => $competition->id, 'member_id' => $brewer->id, 'style_id' => $style->id, 'bottle_number' => 1]);
        $competition->judges()->attach($waiting->id);

        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(ResultsSummaryWidget::class, ['competitionId' => $competition->id])
            ->assertSee('0 / 1')
            ->assertSee('0%')
            ->assertSee('Waiting Wim');
    }
}
