<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use App\Services\BottleNumberAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_bottle_number_assignment_is_sequential(): void
    {
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $comp = Competition::create(['name' => 'Test', 'year' => 2025, 'status' => 'open']);
        $member = Member::create(['name' => 'M', 'email' => 'm@t.com', 'password' => bcrypt('pw'), 'is_active' => true]);

        Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id, 'name' => 'Beer A']);
        Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id, 'name' => 'Beer B']);
        Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id, 'name' => 'Beer C']);

        app(BottleNumberAssignmentService::class)->assign($comp);

        $numbers = Entry::where('competition_id', $comp->id)->orderBy('id')->pluck('bottle_number')->toArray();
        $this->assertEquals([1, 2, 3], $numbers);
    }

    public function test_bottle_numbers_unique_within_competition(): void
    {
        $style = Style::create(['code' => '1B', 'category_name' => 'SA', 'name' => 'American Lager']);
        $comp = Competition::create(['name' => 'Test2', 'year' => 2025, 'status' => 'judging']);
        $member = Member::create(['name' => 'M2', 'email' => 'm2@t.com', 'password' => bcrypt('pw'), 'is_active' => true]);

        $e1 = Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id, 'name' => 'A', 'bottle_number' => 1]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id, 'name' => 'B', 'bottle_number' => 1]);
    }
}
