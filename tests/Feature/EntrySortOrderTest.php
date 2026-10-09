<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class EntrySortOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    private Style $style;

    private Competition $competition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->style = Style::create(['code' => '1A', 'category_name' => 'SA', 'name' => 'Light Lager']);
        $this->competition = Competition::forceCreate(['name' => 'Test Cup', 'year' => 2025, 'status' => CompetitionStatus::Open]);
        $this->competition->styles()->attach($this->style->id);
    }

    public function test_member_entries_get_the_next_sort_orders(): void
    {
        $other = Style::create(['code' => '2A', 'category_name' => 'SA', 'name' => 'Pilsner']);
        $this->competition->styles()->attach($other->id);
        $member = Member::factory()->create();
        Entry::create([
            'competition_id' => $this->competition->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'sort_order' => 7,
        ]);

        $this->actingAs($member)->post(route('competitions.entries.store', $this->competition), ['style_id' => $this->style->id]);
        $this->actingAs($member)->post(route('competitions.entries.store', $this->competition), ['style_id' => $other->id]);

        $this->assertSame(
            [8, 9],
            Entry::where('member_id', $member->id)->orderBy('id')->pluck('sort_order')->all(),
        );
    }

    public function test_explicit_sort_order_is_kept(): void
    {
        $entry = Entry::create([
            'competition_id' => $this->competition->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'sort_order' => 3,
        ]);

        $this->assertSame(3, $entry->sort_order);
    }

    public function test_backfill_fills_null_rows_per_competition(): void
    {
        $other = Competition::forceCreate(['name' => 'Other Cup', 'year' => 2025, 'status' => CompetitionStatus::Open]);
        $other->styles()->attach($this->style->id);

        $make = fn (Competition $c, ?int $order): Entry => Entry::create([
            'competition_id' => $c->id,
            'member_id' => Member::factory()->create()->id,
            'style_id' => $this->style->id,
            'sort_order' => $order,
        ]);

        $kept = $make($this->competition, 5);
        $nullA = $make($this->competition, null);
        $nullB = $make($this->competition, null);
        $otherNull = $make($other, null);

        // Legacy row whose style left the competition: the saving hook would throw on save().
        $orphan = $make($this->competition, null);
        $this->competition->styles()->detach($this->style->id);

        // The creating hook assigns values, so null them the way legacy rows were left.
        Entry::whereKey([$nullA->id, $nullB->id, $otherNull->id, $orphan->id])->update(['sort_order' => null]);
        $this->assertSame(4, Entry::whereNull('sort_order')->count());

        (include database_path('migrations/2026_10_07_193322_backfill_null_entry_sort_order.php'))->up();

        $this->assertSame(0, Entry::whereNull('sort_order')->count());
        $this->assertSame(5, $kept->fresh()->sort_order);
        $this->assertSame([6, 7, 8], [$nullA->fresh()->sort_order, $nullB->fresh()->sort_order, $orphan->fresh()->sort_order]);
        $this->assertSame(1, $otherNull->fresh()->sort_order);
    }
}
