<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use App\Services\BottleNumberAssignmentService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CompetitionLifecycleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_bottle_number_assignment_is_sequential(): void
    {
        $comp = Competition::create(['name' => 'Test', 'year' => 2025, 'status' => 'open']);
        $styles = collect(['1A', '1B', '1C'])->map(fn ($code) => Style::create(['code' => $code, 'category_name' => 'SA', 'name' => $code]));
        $comp->styles()->attach($styles->pluck('id'));
        $member = Member::factory()->create(['name' => 'M', 'email' => 'm@t.com']);

        $styles->each(fn ($s) => Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $s->id]));

        app(BottleNumberAssignmentService::class)->assign($comp);

        $numbers = Entry::where('competition_id', $comp->id)->orderBy('id')->pluck('bottle_number')->toArray();
        $this->assertEquals([1, 2, 3], $numbers);
    }

    public function test_bottle_number_assignment_produces_unique_numbers(): void
    {
        $comp = Competition::create(['name' => 'Test2', 'year' => 2025, 'status' => 'judging']);
        $styles = collect(['2A', '2B', '2C'])->map(fn ($code) => Style::create(['code' => $code, 'category_name' => 'SA', 'name' => $code]));
        $comp->styles()->attach($styles->pluck('id'));
        $member = Member::factory()->create(['name' => 'M2', 'email' => 'm2@t.com']);

        $styles->each(fn ($s) => Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $s->id]));

        app(BottleNumberAssignmentService::class)->assign($comp);

        $numbers = Entry::where('competition_id', $comp->id)->pluck('bottle_number')->toArray();
        $this->assertCount(count($numbers), array_unique($numbers));
    }
}
