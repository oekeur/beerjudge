<?php

namespace App\Services;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Illuminate\Support\Collection;

/**
 * Club-wide, per-member and per-style statistics over closed competitions.
 *
 * Every average, minimum and maximum is taken over scored entries (at least one
 * submitted sheet) using their `avg_total` from CompetitionRankingService::rank().
 * Unscored entries count only toward entry totals and most-entered styles.
 *
 * @phpstan-type RankRow array{entry: Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, \App\Models\Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, band_key: string, placement: int}
 * `entrants` in member history rows is the number of ranked entries in the competition, scored or not,
 * matching the "Nth of N" count on the results sheet.
 * @phpstan-type HistoryRow array{competition_id: int, competition_name: string, judging_date: ?string, entry_id: int, style_label: string, avg_total: ?float, placement: ?int, entrants: int}
 */
class HistoryStatsService
{
    public function __construct(private readonly CompetitionRankingService $ranking) {}

    /**
     * @return Collection<int, array{competition_id: int, name: string, year: int, judging_date: ?string, entries: int, scored_entries: int, participants: int, judges: int, average_total: ?float, winner: ?array{entry_id: int, member_id: int, member_name: string, style_label: string, avg_total: float, is_tied_for_first: bool}}>
     */
    public function competitionSummaries(): Collection
    {
        return $this->closedCompetitions()->map(function (array $data): array {
            $competition = $data['competition'];
            $scored = $data['scored'];
            $top = $scored->first();
            $second = $scored->get(1);

            return [
                'competition_id' => $competition->id,
                'name' => $competition->name,
                'year' => $competition->year,
                'judging_date' => $competition->judging_date?->toDateString(),
                'entries' => $data['rows']->count(),
                'scored_entries' => $scored->count(),
                'participants' => $data['rows']->pluck('entry.member_id')->unique()->count(),
                'judges' => $scored->flatMap(fn (array $row) => $row['sheets']->pluck('judge_id'))->unique()->count(),
                'average_total' => $scored->isEmpty() ? null : round((float) $scored->avg('avg_total'), 2),
                'winner' => $top === null ? null : [
                    'entry_id' => $top['entry']->id,
                    'member_id' => $top['entry']->member_id,
                    'member_name' => (string) $top['entry']->member?->name,
                    'style_label' => $this->styleLabel($top['entry']->style),
                    'avg_total' => $top['avg_total'],
                    'is_tied_for_first' => $second !== null
                        && $second['avg_total'] === $top['avg_total']
                        && $second['avg_overall'] === $top['avg_overall'],
                ],
            ];
        })->values();
    }

    /**
     * Scored entries per band key; all six keys are always present.
     *
     * @return array<string, int>
     */
    public function scoreDistribution(): array
    {
        $distribution = [];
        foreach (CompetitionRankingService::bands() as $band) {
            $distribution[$band['key']] = 0;
        }

        foreach ($this->closedCompetitions() as $data) {
            foreach ($data['scored'] as $row) {
                $distribution[$row['band_key']]++;
            }
        }

        return $distribution;
    }

    /**
     * Counts every entry in closed competitions, scored or not.
     *
     * @return Collection<int, array{style_id: int, label: string, entries: int}>
     */
    public function mostEnteredStyles(int $limit = 10): Collection
    {
        return $this->closedCompetitions()
            ->flatMap(fn (array $data) => $data['rows'])
            ->groupBy(fn (array $row): int => $row['entry']->style_id)
            ->map(fn (Collection $rows, int $styleId): array => [
                'style_id' => $styleId,
                'label' => $this->styleLabel($rows->firstOrFail()['entry']->style),
                'entries' => $rows->count(),
            ])
            ->sortBy([['entries', 'desc'], ['label', 'asc']])
            ->take($limit)
            ->values();
    }

    /**
     * @return array{
     *     rows: list<HistoryRow>,
     *     trend: list<array{competition_id: int, name: string, average_total: float}>,
     *     best: list<HistoryRow>,
     *     by_style: list<array{style_id: int, label: string, entries: int, average_total: float}>
     * }
     */
    public function memberHistory(Member $member): array
    {
        $rows = [];
        $trend = [];
        $scoredByStyle = [];

        foreach ($this->closedCompetitions() as $data) {
            $competition = $data['competition'];
            $own = $data['rows']->filter(fn (array $row): bool => $row['entry']->member_id === $member->id);

            foreach ($own as $row) {
                $isScored = $row['sheet_count'] > 0;
                $rows[] = [
                    'competition_id' => $competition->id,
                    'competition_name' => $competition->name,
                    'judging_date' => $competition->judging_date?->toDateString(),
                    'entry_id' => $row['entry']->id,
                    'style_label' => $this->styleLabel($row['entry']->style),
                    'avg_total' => $isScored ? $row['avg_total'] : null,
                    'placement' => $isScored ? $row['placement'] : null,
                    'entrants' => $data['rows']->count(),
                ];

                if ($isScored) {
                    $scoredByStyle[$row['entry']->style_id][] = $row;
                }
            }

            $ownScored = $own->filter(fn (array $row): bool => $row['sheet_count'] > 0);
            if ($ownScored->isNotEmpty()) {
                $trend[] = [
                    'competition_id' => $competition->id,
                    'name' => $competition->name,
                    'average_total' => round((float) $ownScored->avg('avg_total'), 2),
                ];
            }
        }

        $best = array_values(collect($rows)
            ->filter(fn (array $row): bool => $row['avg_total'] !== null)
            ->sortByDesc('avg_total')
            ->take(5)
            ->all());

        $byStyle = collect($scoredByStyle)
            ->map(fn (array $styleRows, int $styleId): array => [
                'style_id' => $styleId,
                'label' => $this->styleLabel($styleRows[0]['entry']->style),
                'entries' => count($styleRows),
                'average_total' => round((float) collect($styleRows)->avg('avg_total'), 2),
            ])
            ->sortBy([['entries', 'desc'], ['label', 'asc']])
            ->values()
            ->all();

        return ['rows' => $rows, 'trend' => $trend, 'best' => $best, 'by_style' => array_values($byStyle)];
    }

    /**
     * @return Collection<int, array{style_id: int, label: string, scored_entries: int, competitions: int, average_total: float, min_total: float, max_total: float}>
     */
    public function styleStats(): Collection
    {
        $perStyle = [];
        foreach ($this->closedCompetitions() as $data) {
            foreach ($data['scored'] as $row) {
                $perStyle[$row['entry']->style_id][] = [$data['competition']->id, $row];
            }
        }

        return collect($perStyle)
            ->map(function (array $items, int $styleId): array {
                $totals = collect($items)->map(fn (array $item): float => $item[1]['avg_total']);

                return [
                    'style_id' => $styleId,
                    'label' => $this->styleLabel($items[0][1]['entry']->style),
                    'scored_entries' => count($items),
                    'competitions' => collect($items)->pluck(0)->unique()->count(),
                    'average_total' => round((float) $totals->avg(), 2),
                    'min_total' => (float) $totals->min(),
                    'max_total' => (float) $totals->max(),
                ];
            })
            ->sortBy([['scored_entries', 'desc'], ['label', 'asc']])
            ->values();
    }

    /**
     * @return list<array{competition_id: int, name: string, average_total: float, scored_entries: int}>
     */
    public function styleTrend(Style $style): array
    {
        $trend = [];
        foreach ($this->closedCompetitions() as $data) {
            $rows = $data['scored']->filter(fn (array $row): bool => $row['entry']->style_id === $style->id);
            if ($rows->isEmpty()) {
                continue;
            }

            $trend[] = [
                'competition_id' => $data['competition']->id,
                'name' => $data['competition']->name,
                'average_total' => round((float) $rows->avg('avg_total'), 2),
                'scored_entries' => $rows->count(),
            ];
        }

        return $trend;
    }

    /**
     * Closed competitions by judging date ascending (nulls last, then id), sorted in PHP so
     * SQLite and Postgres agree. `rows` holds every ranked entry, `scored` only those with
     * at least one submitted sheet, in placement order.
     *
     * @return Collection<int, array{competition: Competition, rows: Collection<int, RankRow>, scored: Collection<int, RankRow>}>
     */
    private function closedCompetitions(): Collection
    {
        return Competition::query()
            ->where('status', CompetitionStatus::Closed)
            ->get()
            ->sort(function (Competition $a, Competition $b): int {
                $dateA = $a->judging_date?->toDateString();
                $dateB = $b->judging_date?->toDateString();

                return match (true) {
                    $dateA === $dateB => $a->id <=> $b->id,
                    $dateA === null => 1,
                    $dateB === null => -1,
                    default => $dateA <=> $dateB,
                };
            })
            ->values()
            ->map(function (Competition $competition): array {
                $rows = $this->ranking->rank($competition);

                return [
                    'competition' => $competition,
                    'rows' => $rows,
                    'scored' => $rows->filter(fn (array $row): bool => $row['sheets']->isNotEmpty())->values(),
                ];
            });
    }

    private function styleLabel(?Style $style): string
    {
        return $style === null ? '' : $style->code.' '.$style->name;
    }
}
