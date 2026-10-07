<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Widgets\JudgeVarianceWidget;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JudgeVarianceWidgetTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_beer_column_shows_style_code_and_name(): void
    {
        $style = Style::create(['code' => '21A', 'category_name' => 'IPA', 'name' => 'American IPA']);
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $entry = Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
            'bottle_number' => 7,
        ]);

        foreach ([[12, 3, 20, 5, 10], [5, 1, 8, 2, 4]] as $scores) {
            Scoresheet::create([
                'entry_id' => $entry->id,
                'judge_id' => Member::factory()->create()->id,
                'aroma' => $scores[0],
                'appearance' => $scores[1],
                'flavour' => $scores[2],
                'mouthfeel' => $scores[3],
                'overall' => $scores[4],
                'client_uuid' => fake()->uuid(),
                'status' => 'submitted',
            ]);
        }

        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(JudgeVarianceWidget::class, ['competitionId' => $competition->id])
            ->assertSee('21A')
            ->assertSee('American IPA');
    }

    public function test_beer_column_shows_style_name_when_code_is_empty(): void
    {
        $style = Style::create(['code' => '', 'category_name' => 'X', 'name' => 'Nameless Code', 'source' => 'custom']);
        $competition = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'judging']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $entry = Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $brewer->id,
            'style_id' => $style->id,
            'bottle_number' => 7,
        ]);

        foreach ([[12, 3, 20, 5, 10], [5, 1, 8, 2, 4]] as $scores) {
            Scoresheet::create([
                'entry_id' => $entry->id,
                'judge_id' => Member::factory()->create()->id,
                'aroma' => $scores[0],
                'appearance' => $scores[1],
                'flavour' => $scores[2],
                'mouthfeel' => $scores[3],
                'overall' => $scores[4],
                'client_uuid' => fake()->uuid(),
                'status' => 'submitted',
            ]);
        }

        $this->actingAs(Member::factory()->admin()->create());

        Livewire::test(JudgeVarianceWidget::class, ['competitionId' => $competition->id])
            ->assertSee('Nameless Code');
    }
}
