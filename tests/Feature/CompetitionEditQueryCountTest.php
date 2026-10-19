<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Once;
use Livewire\Livewire;
use Tests\TestCase;

class CompetitionEditQueryCountTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Competition $competition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2026, 'status' => 'open']);
    }

    private function seedEntries(int $count): void
    {
        $styles = collect(['1A', '2B', '3C'])->map(fn (string $code) => Style::create([
            'code' => $code, 'category_name' => 'Cat', 'name' => "Style {$code}", 'version' => '2021', 'source' => 'custom',
        ]));
        $this->competition->styles()->attach($styles->pluck('id'));

        for ($i = 0; $i < $count; $i++) {
            Entry::create([
                'competition_id' => $this->competition->id,
                'member_id' => Member::factory()->create()->id,
                'style_id' => $styles[$i % 3]->id,
                'abv' => 5.0,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * @param  callable(): mixed  $action
     * @return array{styles: int, members: int, exists: int}
     */
    private function measure(callable $action): array
    {
        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $action();

        $matching = fn (callable $filter): int => count(array_filter($queries, $filter));

        return [
            'styles' => $matching(fn (string $sql) => str_contains($sql, 'from "styles" where "id" in (') && str_contains($sql, 'order by "code"')),
            'members' => $matching(fn (string $sql) => str_contains($sql, 'from "members" where "members"."id" = ?')),
            'exists' => $matching(fn (string $sql) => str_contains($sql, 'competition_style') && str_contains($sql, 'exists')),
        ];
    }

    private function assertCountsAreIndependentOfEntryCount(int $entries): void
    {
        $this->seedEntries($entries);

        Once::flush();
        $component = null;
        $mount = $this->measure(function () use (&$component): void {
            $component = Livewire::test(EditCompetition::class, ['record' => $this->competition->id]);
        });

        Once::flush();
        $save = $this->measure(fn () => $component->call('save')->assertHasNoFormErrors());

        $this->assertSame(1, $mount['styles'], 'styles queries on mount');
        $this->assertSame(1, $save['styles'], 'styles queries on save');
        $this->assertSame(0, $save['members'], 'member lookups on save');
        $this->assertSame($entries, $save['exists'], 'competition_style exists guard queries on save');
    }

    public function test_two_entries(): void
    {
        $this->assertCountsAreIndependentOfEntryCount(2);
    }

    public function test_six_entries(): void
    {
        $this->assertCountsAreIndependentOfEntryCount(6);
    }
}
