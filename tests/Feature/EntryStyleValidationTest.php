<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EntryStyleValidationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_entry_can_be_created_with_a_style_in_the_competition(): void
    {
        $style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $comp = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'open']);
        $comp->styles()->attach($style->id);
        $member = Member::factory()->create();

        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $style->id]);

        $this->assertDatabaseHas('entries', ['id' => $entry->id, 'style_id' => $style->id]);
    }

    public function test_entry_cannot_be_created_with_a_style_not_in_the_competition(): void
    {
        $allowedStyle = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $otherStyle = Style::create(['code' => '2A', 'category_name' => 'PA', 'name' => 'German Pils']);
        $comp = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'open']);
        $comp->styles()->attach($allowedStyle->id);
        $member = Member::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $otherStyle->id]);
    }

    public function test_entry_cannot_be_updated_to_a_style_not_in_the_competition(): void
    {
        $allowedStyle = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $otherStyle = Style::create(['code' => '2A', 'category_name' => 'PA', 'name' => 'German Pils']);
        $comp = Competition::create(['name' => 'Test Cup', 'year' => 2025, 'status' => 'open']);
        $comp->styles()->attach($allowedStyle->id);
        $member = Member::factory()->create();
        $entry = Entry::create(['competition_id' => $comp->id, 'member_id' => $member->id, 'style_id' => $allowedStyle->id]);

        $this->expectException(\InvalidArgumentException::class);

        $entry->update(['style_id' => $otherStyle->id]);
    }
}
