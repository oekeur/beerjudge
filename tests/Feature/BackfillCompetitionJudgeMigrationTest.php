<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillCompetitionJudgeMigrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_10_14_110000_backfill_competition_judge_from_submitted_scoresheets.php');
        $migration->up();
    }

    private function sheet(Entry $entry, Member $judge, string $status): void
    {
        Scoresheet::create([
            'entry_id' => $entry->id, 'judge_id' => $judge->id, 'aroma' => 8, 'appearance' => 2, 'flavour' => 14,
            'mouthfeel' => 4, 'overall' => 7, 'client_uuid' => fake()->uuid(), 'status' => $status,
        ]);
    }

    public function test_backfill_adds_submitted_scorers_once_and_ignores_drafts_and_existing_rows(): void
    {
        $competition = Competition::forceCreate(['name' => 'Cup', 'year' => 2025, 'status' => 'judging']);
        $style = Style::create(['code' => '1A', 'category_name' => 'Standard American', 'name' => 'Light Lager']);
        $competition->styles()->attach($style->id);
        $brewer = Member::factory()->create();
        $first = Entry::create(['competition_id' => $competition->id, 'member_id' => $brewer->id, 'style_id' => $style->id]);
        $second = Entry::create(['competition_id' => $competition->id, 'member_id' => Member::factory()->create()->id, 'style_id' => $style->id]);
        [$scorer, $registered, $draftOnly] = Member::factory()->count(3)->create();
        $competition->judges()->attach($registered->id);

        $this->sheet($first, $scorer, 'submitted');
        $this->sheet($second, $scorer, 'submitted');
        $this->sheet($first, $registered, 'submitted');
        $this->sheet($first, $draftOnly, 'draft');

        $this->runBackfill();

        $this->assertEqualsCanonicalizing(
            [$scorer->id, $registered->id],
            $competition->judges()->pluck('members.id')->all()
        );

        $before = DB::table('competition_judge')->count();
        $this->runBackfill();
        $this->assertSame($before, DB::table('competition_judge')->count());
    }
}
