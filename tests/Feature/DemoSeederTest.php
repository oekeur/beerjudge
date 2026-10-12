<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_demo_entries_get_the_styles_the_seeder_names(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $autumn = Competition::where('name', 'Autumn Harvest 2024')->firstOrFail();

        $codesByBottle = Entry::where('competition_id', $autumn->id)
            ->with('style')
            ->orderBy('bottle_number')
            ->get()
            ->mapWithKeys(fn (Entry $entry): array => [$entry->bottle_number => $entry->style->code])
            ->all();

        $this->assertSame('1A', $codesByBottle[1]);
        $this->assertSame('4A', $codesByBottle[2]);
    }

    public function test_demo_entries_only_use_bjcp_styles(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $sources = Entry::with('style')->get()->pluck('style.source')->unique()->values()->all();

        $this->assertSame(['bjcp_beer'], $sources);
    }
}
