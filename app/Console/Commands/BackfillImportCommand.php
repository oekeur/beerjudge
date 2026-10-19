<?php

namespace App\Console\Commands;

use App\Enums\CompetitionStatus;
use App\Enums\ScoresheetStatus;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use App\Models\Style;
use App\Services\BottleNumberAssignmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Signature('backfill:import {json : Path to the extracted competition JSON} {names : Path to the filled-in names_to_map.csv} {beers : Path to the filled-in beers_to_map.csv} {--dry-run : Validate and preview without writing to the database}')]
#[Description('Import a historical competition from Google Forms judging data extracted by backfill/extract_responses.py')]
class BackfillImportCommand extends Command
{
    /**
     * @var array<string, int>
     */
    private array $names = [];

    /**
     * @var array<string, array{style_ref: string, brewer_member_id: int, is_co_brew: bool, raw_brewer_name: string}>
     */
    private array $beers = [];

    /**
     * @var array<int, string>
     */
    private array $loadErrors = [];

    public function handle(): int
    {
        $jsonPath = $this->stringArgument('json');
        $namesPath = $this->stringArgument('names');
        $beersPath = $this->stringArgument('beers');

        if (! is_file($jsonPath)) {
            $this->error("JSON file not found: {$jsonPath}");

            return self::FAILURE;
        }

        if (! is_file($namesPath)) {
            $this->error("Names CSV not found: {$namesPath}");

            return self::FAILURE;
        }

        if (! is_file($beersPath)) {
            $this->error("Beers CSV not found: {$beersPath}");

            return self::FAILURE;
        }

        /** @var array{competition_name: string, slug: string, records: array<int, array<string, mixed>>} $data */
        $data = json_decode((string) file_get_contents($jsonPath), true);

        $this->loadErrors = [];
        $this->names = $this->loadNamesCsv($namesPath);
        $this->beers = $this->loadBeersCsv($beersPath, $data['competition_name']);

        $errors = [...$this->loadErrors, ...$this->validateRecords($data['records'])];

        if ($errors !== []) {
            $this->error('Import aborted, unresolved mappings found:');
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }

            return self::FAILURE;
        }

        $timestamps = [];
        foreach ($data['records'] as $record) {
            $timestamps[] = $this->scalarToString($record['timestamp']);
        }
        sort($timestamps);
        $earliestTimestamp = $timestamps[0];
        $year = (int) substr($earliestTimestamp, 0, 4);

        $existing = Competition::where('name', $data['competition_name'])->where('year', $year)->first();

        if ($existing !== null) {
            $this->error("Import aborted: competition '{$data['competition_name']}' ({$year}) already exists (id {$existing->id}). Nothing was written.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $summary = ['entries' => 0, 'scoresheets' => 0, 'total_mismatches' => []];

        try {
            DB::transaction(function () use ($data, &$summary, $dryRun, $year, $earliestTimestamp): void {
                // forceCreate with the final status: a later status change would fire the
                // Competition::booted hook and mail every participant. results_mailed_at
                // marks the results as already sent.
                $competition = Competition::forceCreate([
                    'name' => $data['competition_name'],
                    'year' => $year,
                    'judging_date' => substr($earliestTimestamp, 0, 10),
                    'status' => CompetitionStatus::Closed,
                    'results_mailed_at' => now(),
                ]);

                $styleModels = [];
                foreach ($this->beers as $beer) {
                    if (! isset($styleModels[$beer['style_ref']])) {
                        $styleModels[$beer['style_ref']] = $this->resolveStyle($beer['style_ref']);
                    }
                }

                $styleTableIds = [];
                foreach ($styleModels as $style) {
                    $styleTableIds[$style->id] = $style->id;
                }
                $competition->styles()->attach(array_values($styleTableIds));

                $judgeIds = [];
                foreach ($data['records'] as $record) {
                    $judgeName = $this->scalarToString($record['judge_name']);
                    $judgeIds[$this->names[$judgeName]] = $this->names[$judgeName];
                }
                $competition->judges()->attach(array_values($judgeIds));

                $entriesByBeerText = [];
                foreach ($this->beers as $beerText => $beer) {
                    $notes = $beer['is_co_brew'] ? "Co-brewed: {$beer['raw_brewer_name']}" : null;

                    $entriesByBeerText[$beerText] = Entry::create([
                        'competition_id' => $competition->id,
                        'member_id' => $beer['brewer_member_id'],
                        'style_id' => $styleModels[$beer['style_ref']]->id,
                        'brewer_notes' => $notes,
                    ]);
                }

                app(BottleNumberAssignmentService::class)->assign($competition);

                $summary['entries'] = count($entriesByBeerText);

                foreach ($data['records'] as $record) {
                    $beerText = $this->scalarToString($record['beer_text']);
                    $judgeName = $this->scalarToString($record['judge_name']);
                    $entry = $entriesByBeerText[$beerText];

                    $sheet = Scoresheet::create([
                        'entry_id' => $entry->id,
                        'judge_id' => $this->names[$judgeName],
                        'aroma' => $this->sectionScore($record['aroma']),
                        'appearance' => $this->sectionScore($record['appearance']),
                        'flavour' => $this->sectionScore($record['flavour']),
                        'mouthfeel' => $this->sectionScore($record['mouthfeel']),
                        'overall' => $this->sectionScore($record['overall']),
                        'aroma_notes' => $record['aroma_notes'],
                        'appearance_notes' => $record['appearance_notes'],
                        'flavour_notes' => $record['flavour_notes'],
                        'mouthfeel_notes' => $record['mouthfeel_notes'],
                        'overall_notes' => $record['overall_notes'],
                        'client_uuid' => (string) Str::uuid(),
                        'status' => ScoresheetStatus::Submitted,
                        'submitted_at' => $record['timestamp'],
                    ]);

                    $summary['scoresheets']++;

                    $rawSheetTotal = $record['sheet_total'];
                    if ($rawSheetTotal !== null) {
                        $sheetTotal = (float) $this->scalarToString($rawSheetTotal);
                        if ($sheetTotal !== (float) $sheet->total) {
                            $summary['total_mismatches'][] = "{$judgeName} / {$beerText}: sheet says {$sheetTotal}, computed {$sheet->total}";
                        }
                    }
                }

                if ($dryRun) {
                    throw new \RuntimeException('backfill:import dry-run rollback');
                }
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'backfill:import dry-run rollback') {
                throw $e;
            }
        }

        $this->info(($dryRun ? '[dry run] ' : '')."Competition: {$data['competition_name']}");
        $this->info("Entries: {$summary['entries']}");
        $this->info("Scoresheets: {$summary['scoresheets']}");

        foreach ($summary['total_mismatches'] as $mismatch) {
            $this->warn("Total mismatch — {$mismatch}");
        }

        return self::SUCCESS;
    }

    private function stringArgument(string $name): string
    {
        $value = $this->argument($name);

        return is_string($value) ? $value : '';
    }

    /**
     * Validation has already rejected anything that is not a whole number in range.
     */
    private function sectionScore(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    private function scalarToString(mixed $value): string
    {
        if (! is_scalar($value)) {
            throw new \RuntimeException('Expected a scalar value in extracted record data, got '.get_debug_type($value).'.');
        }

        return (string) $value;
    }

    /**
     * Reads a CSV into header-keyed rows. Strips a UTF-8 BOM from the header and skips blank lines.
     *
     * @return array<int, array<string, string>>
     */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException("Cannot open CSV file: {$path}");
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            throw new \RuntimeException("Cannot read CSV header from: {$path}");
        }

        /** @var array<int, string> $header */
        $header = array_map(strval(...), $header);
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]) ?? $header[0];

        $rows = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if ($row === [null]) {
                continue;
            }

            /** @var array<int, string> $row */
            $row = array_map(strval(...), $row);

            if (count($row) !== count($header)) {
                $this->loadErrors[] = basename($path)." line {$lineNumber}: expected ".count($header).' columns, found '.count($row);

                continue;
            }

            $rows[] = array_combine($header, $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return array<string, int>
     */
    private function loadNamesCsv(string $path): array
    {
        $names = [];

        foreach ($this->readCsv($path) as $line) {
            if ($line['member_id'] === '') {
                continue;
            }

            $memberId = (int) $line['member_id'];

            if (isset($names[$line['name']]) && $names[$line['name']] !== $memberId) {
                $this->loadErrors[] = "Conflicting names_to_map rows for '{$line['name']}': member_id {$names[$line['name']]} and {$memberId}";
            }

            $names[$line['name']] = $memberId;
        }

        return $names;
    }

    /**
     * @return array<string, array{style_ref: string, brewer_member_id: int, is_co_brew: bool, raw_brewer_name: string}>
     */
    private function loadBeersCsv(string $path, string $competitionName): array
    {
        $beers = [];

        foreach ($this->readCsv($path) as $line) {
            if ($line['competition'] !== $competitionName) {
                continue;
            }

            $beer = [
                'style_ref' => $line['bjcp_code'],
                'brewer_member_id' => $line['brewer_member_id'] === '' ? 0 : (int) $line['brewer_member_id'],
                'is_co_brew' => str_contains($line['brewer_name'], '&'),
                'raw_brewer_name' => $line['brewer_name'],
            ];

            $key = $line['raw_beer_text'];

            if (isset($beers[$key]) && ($beers[$key]['style_ref'] !== $beer['style_ref'] || $beers[$key]['brewer_member_id'] !== $beer['brewer_member_id'])) {
                $this->loadErrors[] = "Conflicting beers_to_map rows for '{$key}': ({$beers[$key]['style_ref']}, member {$beers[$key]['brewer_member_id']}) and ({$beer['style_ref']}, member {$beer['brewer_member_id']})";
            }

            $beers[$key] = $beer;
        }

        return $beers;
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, string>
     */
    private function validateRecords(array $records): array
    {
        $errors = [];
        $checkedNames = [];
        $checkedBeers = [];
        $checkedBrewerStyles = [];
        $judgedPairs = [];

        foreach ($records as $record) {
            $judgeName = $this->scalarToString($record['judge_name']);

            if (! isset($checkedNames[$judgeName])) {
                $checkedNames[$judgeName] = true;

                if (! isset($this->names[$judgeName])) {
                    $errors[] = "Unmapped judge name: '{$judgeName}' (fill member_id in names_to_map.csv)";
                } elseif (Member::whereKey($this->names[$judgeName])->doesntExist()) {
                    $errors[] = "Unknown member_id {$this->names[$judgeName]} for judge: '{$judgeName}'";
                }
            }

            $beerText = $this->scalarToString($record['beer_text']);

            if (! isset($checkedBeers[$beerText])) {
                $checkedBeers[$beerText] = true;

                if (! isset($this->beers[$beerText])) {
                    $errors[] = "Unmapped beer: '{$beerText}' (not found in beers_to_map.csv for this competition)";

                    continue;
                }

                $beer = $this->beers[$beerText];

                if ($beer['style_ref'] !== '' && $beer['brewer_member_id'] !== 0) {
                    $key = $beer['brewer_member_id'].'|'.$beer['style_ref'];

                    if (isset($checkedBrewerStyles[$key])) {
                        $errors[] = "Brewer {$beer['brewer_member_id']} has two beers of style '{$beer['style_ref']}' ('{$checkedBrewerStyles[$key]}' and '{$beerText}'); an entry is unique per brewer and style";
                    }

                    $checkedBrewerStyles[$key] = $beerText;
                }

                if ($beer['style_ref'] === '') {
                    $errors[] = "Missing bjcp_code for beer: '{$beerText}'";
                }

                if ($beer['brewer_member_id'] === 0) {
                    $errors[] = "Missing brewer_member_id for beer: '{$beerText}'";
                }

                if ($beer['style_ref'] !== '' && ! str_starts_with($beer['style_ref'], 'custom:')
                    && Style::where('source', 'bjcp_beer')->where('code', $beer['style_ref'])->doesntExist()) {
                    $errors[] = "Unknown BJCP code '{$beer['style_ref']}' for beer: '{$beerText}'";
                }

                if ($beer['brewer_member_id'] !== 0 && Member::whereKey($beer['brewer_member_id'])->doesntExist()) {
                    $errors[] = "Unknown member_id {$beer['brewer_member_id']} for beer: '{$beerText}'";
                }
            }

            $this->validateRecordSheet($record, $judgeName, $beerText, $errors, $judgedPairs);
        }

        return $errors;
    }

    /**
     * Checks one record: section scores, own-entry judging and duplicate sheets.
     *
     * @param  array<string, mixed>  $record
     * @param  array<int, string>  $errors
     * @param  array<string, true>  $judgedPairs
     */
    private function validateRecordSheet(array $record, string $judgeName, string $beerText, array &$errors, array &$judgedPairs): void
    {
        foreach (Scoresheet::SECTION_MAX as $section => $max) {
            $value = $record[$section] ?? null;
            $isWholeNumber = is_int($value) || (is_float($value) && floor($value) === $value);

            if (! $isWholeNumber || $value < 0 || $value > $max) {
                $shown = var_export($value, true);
                $errors[] = "Invalid {$section} score {$shown} (whole number 0-{$max} required) from judge '{$judgeName}' for beer: '{$beerText}'";
            }
        }

        $judgeId = $this->names[$judgeName] ?? null;
        $beer = $this->beers[$beerText] ?? null;

        if ($judgeId === null || $beer === null) {
            return;
        }

        if ($beer['brewer_member_id'] === $judgeId) {
            $errors[] = "Judge '{$judgeName}' scored their own beer: '{$beerText}'";
        }

        $pair = $judgeId.'|'.$beerText;

        if (isset($judgedPairs[$pair])) {
            $errors[] = "Duplicate scoresheet: member {$judgeId} (judge '{$judgeName}') scored beer '{$beerText}' more than once";
        }

        $judgedPairs[$pair] = true;
    }

    private function resolveStyle(string $ref): Style
    {
        if (str_starts_with($ref, 'custom:')) {
            $name = substr($ref, strlen('custom:'));

            return Style::firstOrCreate(
                ['source' => 'custom', 'name' => $name],
                [
                    'code' => strtoupper(substr(md5($name), 0, 8)),
                    'category_name' => 'Historical Import',
                    'version' => null,
                ],
            );
        }

        return Style::where('source', 'bjcp_beer')->where('code', $ref)->firstOrFail();
    }
}
