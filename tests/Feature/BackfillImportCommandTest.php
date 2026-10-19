<?php

namespace Tests\Feature;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use App\Notifications\CompetitionResultsNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\PendingCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BackfillImportCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $fixtureDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureDir = sys_get_temp_dir().'/backfill-import-test-'.uniqid();
        mkdir($this->fixtureDir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->fixtureDir.'/*') ?: []);
        rmdir($this->fixtureDir);

        parent::tearDown();
    }

    private function writeJson(array $data): string
    {
        $path = $this->fixtureDir.'/competition.json';
        file_put_contents($path, json_encode($data, JSON_PRESERVE_ZERO_FRACTION));

        return $path;
    }

    private function writeCsv(string $name, array $header, array $rows): string
    {
        $path = $this->fixtureDir."/{$name}.csv";
        $handle = fopen($path, 'w');
        fputcsv($handle, $header);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return $path;
    }

    private function baseRecord(array $overrides = []): array
    {
        return array_merge([
            'judge_name' => 'Alice',
            'beer_text' => "1. Carol's Cream Ale",
            'brewer_name' => 'Carol',
            'style_text' => 'Cream Ale',
            'aroma' => 9,
            'aroma_notes' => 'Grainy',
            'appearance' => 3,
            'appearance_notes' => 'Pale, clear',
            'flavour' => 16,
            'flavour_notes' => 'Clean',
            'mouthfeel' => 4,
            'mouthfeel_notes' => 'Light',
            'overall' => 8,
            'overall_notes' => 'Good session beer',
            'sheet_total' => 40,
            'timestamp' => '2025-03-11T20:43:13.174000',
        ], $overrides);
    }

    public function test_import_creates_competition_entries_and_scoresheets(): void
    {
        $alice = Member::factory()->create(['name' => 'Alice Judge']);
        $bob = Member::factory()->create(['name' => 'Bob Judge']);
        $carol = Member::factory()->create(['name' => 'Carol Brewer']);

        $jsonPath = $this->writeJson([
            'competition_name' => 'Test Backfill Cup',
            'slug' => 'test-backfill-cup',
            'records' => [
                $this->baseRecord(['judge_name' => 'Alice']),
                $this->baseRecord(['judge_name' => 'Bob', 'aroma' => 8, 'overall' => 7, 'sheet_total' => 38]),
            ],
        ]);

        $namesPath = $this->writeCsv('names', ['name', 'seen_as_judge', 'seen_as_brewer', 'occurrences', 'member_id'], [
            ['Alice', 1, 0, 1, (string) $alice->id],
            ['Bob', 1, 0, 1, (string) $bob->id],
            ['Carol', 0, 1, 1, (string) $carol->id],
        ]);

        $beersPath = $this->writeCsv('beers', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'], [
            ['Test Backfill Cup', "1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id],
        ]);

        $this->artisan('backfill:import', ['json' => $jsonPath, 'names' => $namesPath, 'beers' => $beersPath])
            ->assertSuccessful();

        $this->assertDatabaseHas('competitions', ['name' => 'Test Backfill Cup', 'status' => 'closed']);
        $competition = Competition::where('name', 'Test Backfill Cup')->firstOrFail();

        $style = Style::where('source', 'custom')->where('name', 'Cream Ale')->first();
        $this->assertNotNull($style);
        $this->assertTrue($competition->styles->contains($style));
        $this->assertTrue($competition->judges->pluck('id')->contains($alice->id));
        $this->assertTrue($competition->judges->pluck('id')->contains($bob->id));

        $entry = Entry::where('competition_id', $competition->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame($carol->id, $entry->member_id);
        $this->assertSame($style->id, $entry->style_id);

        $this->assertSame(2, Scoresheet::where('entry_id', $entry->id)->count());
        $aliceSheet = Scoresheet::where('entry_id', $entry->id)->where('judge_id', $alice->id)->firstOrFail();
        $this->assertSame(9 + 3 + 16 + 4 + 8, $aliceSheet->total);
        $this->assertSame('submitted', $aliceSheet->status->value);
    }

    public function test_dry_run_does_not_write_to_database(): void
    {
        $alice = Member::factory()->create(['name' => 'Alice Judge']);
        $carol = Member::factory()->create(['name' => 'Carol Brewer']);

        $jsonPath = $this->writeJson([
            'competition_name' => 'Dry Run Cup',
            'slug' => 'dry-run-cup',
            'records' => [$this->baseRecord()],
        ]);

        $namesPath = $this->writeCsv('names', ['name', 'seen_as_judge', 'seen_as_brewer', 'occurrences', 'member_id'], [
            ['Alice', 1, 0, 1, (string) $alice->id],
            ['Carol', 0, 1, 1, (string) $carol->id],
        ]);

        $beersPath = $this->writeCsv('beers', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'], [
            ['Dry Run Cup', "1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id],
        ]);

        $this->artisan('backfill:import', ['json' => $jsonPath, 'names' => $namesPath, 'beers' => $beersPath, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('competitions', ['name' => 'Dry Run Cup']);
    }

    public function test_import_aborts_when_a_judge_name_is_unmapped(): void
    {
        $carol = Member::factory()->create(['name' => 'Carol Brewer']);

        $jsonPath = $this->writeJson([
            'competition_name' => 'Unmapped Cup',
            'slug' => 'unmapped-cup',
            'records' => [$this->baseRecord(['judge_name' => 'Someone Unmapped'])],
        ]);

        $namesPath = $this->writeCsv('names', ['name', 'seen_as_judge', 'seen_as_brewer', 'occurrences', 'member_id'], [
            ['Someone Unmapped', 1, 0, 1, ''],
            ['Carol', 0, 1, 1, (string) $carol->id],
        ]);

        $beersPath = $this->writeCsv('beers', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'], [
            ['Unmapped Cup', "1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id],
        ]);

        $this->artisan('backfill:import', ['json' => $jsonPath, 'names' => $namesPath, 'beers' => $beersPath])
            ->assertFailed();

        $this->assertDatabaseMissing('competitions', ['name' => 'Unmapped Cup']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, array<int, string>>  $beerRows  rows after the competition column
     * @return array{json: string, names: string, beers: string, alice: Member, carol: Member}
     */
    private function fixture(string $competition, array $records, ?array $beerRows = null): array
    {
        $alice = Member::factory()->create(['name' => 'Alice Judge']);
        $carol = Member::factory()->create(['name' => 'Carol Brewer']);

        $json = $this->writeJson([
            'competition_name' => $competition,
            'slug' => 'fixture',
            'records' => $records,
        ]);

        $names = $this->writeCsv('names', ['name', 'seen_as_judge', 'seen_as_brewer', 'occurrences', 'member_id'], [
            ['Alice', 1, 0, 1, (string) $alice->id],
            ['Carol', 0, 1, 1, (string) $carol->id],
        ]);

        $beerRows ??= [["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id]];
        $beers = $this->writeCsv('beers', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'],
            array_map(fn (array $row) => [$competition, ...$row], $beerRows));

        return ['json' => $json, 'names' => $names, 'beers' => $beers, 'alice' => $alice, 'carol' => $carol];
    }

    public function test_import_creates_closed_competition_already_marked_as_mailed(): void
    {
        Notification::fake();
        $f = $this->fixture('Mailed Cup', [$this->baseRecord()]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])->assertSuccessful();

        $competition = Competition::where('name', 'Mailed Cup')->firstOrFail();
        $this->assertSame(CompetitionStatus::Closed, $competition->status);
        $this->assertNotNull($competition->results_mailed_at);
        Notification::assertNothingSent();

        $competition->transitionTo(CompetitionStatus::Draft);
        $competition->transitionTo(CompetitionStatus::Closed);
        Notification::assertNothingSent();
    }

    public function test_a_competition_without_results_mailed_at_does_send_on_close(): void
    {
        Notification::fake();
        $f = $this->fixture('Control Cup', [$this->baseRecord()]);
        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])->assertSuccessful();

        $copy = Competition::forceCreate(['name' => 'Copy', 'year' => 2025, 'judging_date' => '2025-03-11', 'status' => CompetitionStatus::Draft]);
        $styleId = Entry::firstOrFail()->style_id;
        $copy->styles()->attach($styleId);
        Entry::forceCreate(['competition_id' => $copy->id, 'member_id' => $f['carol']->id, 'style_id' => $styleId]);
        $copy->transitionTo(CompetitionStatus::Closed);

        Notification::assertSentTo($f['carol'], CompetitionResultsNotification::class);
    }

    public function test_every_entry_gets_a_unique_bottle_number(): void
    {
        $dave = Member::factory()->create(['name' => 'Dave Brewer']);
        $f = $this->fixture('Bottle Cup', [
            $this->baseRecord(),
            $this->baseRecord(['beer_text' => "2. Dave's Stout", 'brewer_name' => 'Dave']),
        ]);
        $beers = $this->writeCsv('beers2', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'], [
            ['Bottle Cup', "1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $f['carol']->id],
            ['Bottle Cup', "2. Dave's Stout", 'Dave', 'Stout', 'custom:Stout', (string) $dave->id],
        ]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $beers])->assertSuccessful();

        $numbers = Entry::pluck('bottle_number');
        $this->assertCount(2, $numbers);
        $this->assertNotContains(null, $numbers->all());
        $this->assertCount(2, $numbers->unique());
    }

    public function test_total_check_compares_numerically_not_by_type(): void
    {
        $f = $this->fixture('Total Cup', [
            $this->baseRecord(['aroma' => 9.0, 'appearance' => 3.0, 'flavour' => 16.0, 'mouthfeel' => 4.0, 'overall' => 8.0, 'sheet_total' => 40]),
        ]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertSuccessful()
            ->doesntExpectOutputToContain('Total mismatch');
    }

    public function test_a_genuinely_different_total_is_reported_once(): void
    {
        $f = $this->fixture('Mismatch Cup', [$this->baseRecord(['sheet_total' => 41])]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertSuccessful()
            ->expectsOutputToContain('sheet says 41, computed 40');
    }

    public function test_second_import_of_the_same_competition_aborts_without_writing(): void
    {
        $f = $this->fixture('Rerun Cup', [$this->baseRecord()]);
        $args = ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']];

        $this->artisan('backfill:import', $args)->assertSuccessful();
        $this->artisan('backfill:import', $args)->assertExitCode(1);

        $this->assertSame(1, Competition::where('name', 'Rerun Cup')->count());
        $this->assertSame(1, Entry::count());
        $this->assertSame(1, Scoresheet::count());
    }

    public function test_same_brewer_with_two_beers_of_one_style_aborts_readably(): void
    {
        $f = $this->fixture('Twin Cup', [
            $this->baseRecord(),
            $this->baseRecord(['beer_text' => "2. Carol's Other Cream Ale"]),
        ], null);
        $beers = $this->writeCsv('beers3', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'], [
            ['Twin Cup', "1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $f['carol']->id],
            ['Twin Cup', "2. Carol's Other Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $f['carol']->id],
        ]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $beers])
            ->assertExitCode(1)
            ->expectsOutputToContain("2. Carol's Other Cream Ale");

        $this->assertDatabaseMissing('competitions', ['name' => 'Twin Cup']);
    }

    public function test_import_aborts_when_a_beer_has_no_brewer_mapping(): void
    {
        $f = $this->fixture('Unbrewed Cup', [$this->baseRecord()], [["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', '']]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertExitCode(1)
            ->expectsOutputToContain('Missing brewer_member_id');

        $this->assertDatabaseMissing('competitions', ['name' => 'Unbrewed Cup']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @param  array<int, array<int, mixed>>  $names
     * @param  array<int, array<int, mixed>>  $beers
     */
    private function runRaw(array $records, array $names, array $beers, string $competition = 'Edge Cup'): PendingCommand
    {
        $json = $this->writeJson(['competition_name' => $competition, 'slug' => 'edge', 'records' => $records]);
        $namesPath = $this->writeCsv('names', ['name', 'seen_as_judge', 'seen_as_brewer', 'occurrences', 'member_id'], $names);
        $beersPath = $this->writeCsv('beers', ['competition', 'raw_beer_text', 'brewer_name', 'style_text_guess', 'bjcp_code', 'brewer_member_id'],
            array_map(fn (array $row) => [$competition, ...$row], $beers));

        return $this->artisan('backfill:import', ['json' => $json, 'names' => $namesPath, 'beers' => $beersPath]);
    }

    private function assertNothingWritten(): void
    {
        $this->assertSame([0, 0, 0], [Competition::count(), Entry::count(), Scoresheet::count()]);
    }

    public function test_judge_scoring_their_own_beer_is_rejected(): void
    {
        $alice = Member::factory()->create();

        $this->runRaw([$this->baseRecord()], [['Alice', 1, 1, 1, (string) $alice->id]], [["1. Carol's Cream Ale", 'Alice', 'Cream Ale', 'custom:Cream Ale', (string) $alice->id]])
            ->assertExitCode(1)
            ->expectsOutputToContain("Judge 'Alice' scored their own beer: '1. Carol's Cream Ale'");

        $this->assertNothingWritten();
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidSectionProvider(): array
    {
        return [
            'above max' => [['aroma' => 15], 'Invalid aroma score 15 (whole number 0-12 required) from judge \'Alice\' for beer'],
            'null' => [['flavour' => null], 'Invalid flavour score NULL'],
            'negative' => [['mouthfeel' => -1], 'Invalid mouthfeel score -1'],
            'fractional' => [['aroma' => 8.5], 'Invalid aroma score 8.5'],
        ];
    }

    #[DataProvider('invalidSectionProvider')]
    public function test_invalid_section_scores_are_rejected(array $override, string $message): void
    {
        $f = $this->fixture('Edge Cup', [$this->baseRecord($override)]);
        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertExitCode(1)
            ->expectsOutputToContain("{$message}");

        $this->assertNothingWritten();
    }

    public function test_conflicting_duplicate_beer_rows_are_rejected_but_identical_repeats_pass(): void
    {
        $alice = Member::factory()->create();
        $carol = Member::factory()->create();
        $dave = Member::factory()->create();
        $names = [['Alice', 1, 0, 1, (string) $alice->id]];
        $row = ["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id];

        $this->runRaw([$this->baseRecord()], $names, [$row, ["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Other', (string) $dave->id]])
            ->assertExitCode(1)
            ->expectsOutputToContain("Conflicting beers_to_map rows for '1. Carol's Cream Ale'");
        $this->assertNothingWritten();

        $this->runRaw([$this->baseRecord()], $names, [$row, $row])->assertSuccessful();
    }

    public function test_conflicting_duplicate_name_rows_are_rejected(): void
    {
        $alice = Member::factory()->create();
        $other = Member::factory()->create();
        $carol = Member::factory()->create();

        $this->runRaw([$this->baseRecord()], [['Alice', 1, 0, 1, (string) $alice->id], ['Alice', 1, 0, 1, (string) $other->id]],
            [["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id]])
            ->assertExitCode(1)
            ->expectsOutputToContain("Conflicting names_to_map rows for 'Alice'");

        $this->assertNothingWritten();
    }

    public function test_two_spellings_of_one_judge_on_the_same_beer_are_rejected(): void
    {
        $alice = Member::factory()->create();
        $carol = Member::factory()->create();

        $this->runRaw([$this->baseRecord(['judge_name' => 'Alice']), $this->baseRecord(['judge_name' => 'Alice.'])],
            [['Alice', 1, 0, 1, (string) $alice->id], ['Alice.', 1, 0, 1, (string) $alice->id]],
            [["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id]])
            ->assertExitCode(1)
            ->expectsOutputToContain('Duplicate scoresheet');

        $this->assertNothingWritten();
    }

    public function test_one_judge_submitting_twice_for_a_beer_is_rejected(): void
    {
        $f = $this->fixture('Edge Cup', [$this->baseRecord(), $this->baseRecord()]);

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertExitCode(1)
            ->expectsOutputToContain('Duplicate scoresheet');

        $this->assertNothingWritten();
    }

    public function test_unknown_judge_member_id_is_rejected(): void
    {
        $carol = Member::factory()->create();

        $this->runRaw([$this->baseRecord()], [['Alice', 1, 0, 1, '99999']],
            [["1. Carol's Cream Ale", 'Carol', 'Cream Ale', 'custom:Cream Ale', (string) $carol->id]])
            ->assertExitCode(1)
            ->expectsOutputToContain("Unknown member_id 99999 for judge: 'Alice'");

        $this->assertNothingWritten();
    }

    public function test_csv_with_bom_and_blank_lines_is_read(): void
    {
        $f = $this->fixture('Edge Cup', [$this->baseRecord()]);
        foreach (['names', 'beers'] as $key) {
            $lines = file($f[$key], FILE_IGNORE_NEW_LINES);
            $withBlanks = "\xEF\xBB\xBF".$lines[0]."\n\n".implode("\n\n", array_slice($lines, 1))."\n\n";
            file_put_contents($f[$key], $withBlanks);
        }

        $this->artisan('backfill:import', ['json' => $f['json'], 'names' => $f['names'], 'beers' => $f['beers']])
            ->assertSuccessful();

        $this->assertSame(1, Scoresheet::count());
    }
}
