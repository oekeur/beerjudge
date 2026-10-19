<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Entry;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_reopening_and_closing_the_closed_demo_competition_sends_no_mail(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);

        $autumn = Competition::where('name', 'Autumn Harvest 2024')->firstOrFail();
        $this->assertTrue($autumn->activeParticipants()->exists());
        $this->assertNotNull($autumn->results_mailed_at);

        Notification::fake();

        $autumn->transitionTo(CompetitionStatus::Judging);
        $autumn->transitionTo(CompetitionStatus::Closed);

        Notification::assertNothingSent();
        $this->assertNotNull($autumn->fresh()->results_mailed_at);
    }
}
