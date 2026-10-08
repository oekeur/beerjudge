<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\CompetitionResource;
use App\Filament\Resources\Competitions\Widgets\ResultsSummaryWidget;
use App\Models\Competition;
use App\Models\Member;
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
}
