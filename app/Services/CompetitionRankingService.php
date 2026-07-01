<?php

namespace App\Services;

use App\Enums\ScoresheetStatus;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Scoresheet;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class CompetitionRankingService
{
    /**
     * @return Collection<int, array{entry: Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, band_key: string, placement: int}>
     */
    public function rank(Competition $competition): Collection
    {
        $entries = Entry::where('competition_id', $competition->id)
            ->with(['member', 'style', 'scoresheets' => fn (Relation $query) => $query->where('status', ScoresheetStatus::Submitted)->with('judge')])
            ->orderBy('id')
            ->get();

        return $entries
            ->map(function (Entry $entry): array {
                $sheets = $entry->scoresheets;
                $averageTotal = round((float) $sheets->avg('total'), 2);
                $averageOverall = round((float) $sheets->avg('overall'), 2);

                return [
                    'entry' => $entry,
                    'sheets' => $sheets,
                    'avg_total' => $averageTotal,
                    'avg_overall' => $averageOverall,
                    'sheet_count' => $sheets->count(),
                    'band' => $this->band($averageTotal),
                    'band_key' => $this->bandKey($averageTotal),
                ];
            })
            ->sort(fn (array $a, array $b): int => $b['avg_total'] <=> $a['avg_total']
                ?: $b['avg_overall'] <=> $a['avg_overall'])
            ->values()
            ->map(function (array $row, int $index): array {
                $row['placement'] = $index + 1;

                return $row;
            });
    }

    /**
     * @param  Collection<array-key, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public static function sortAndPlace(Collection $rows): Collection
    {
        $items = $rows->all();

        usort($items, fn (array $a, array $b): int => $b['avg_total'] <=> $a['avg_total']
            ?: $b['avg_overall'] <=> $a['avg_overall']);

        foreach (array_keys($items) as $index) {
            $items[$index]['placement'] = $index + 1;
        }

        return collect($items);
    }

    /**
     * @return array<int, array{min: int, key: string, name: string, range: string}>
     */
    public static function bands(): array
    {
        return [
            ['min' => 45, 'key' => 'outstanding', 'name' => 'Outstanding', 'range' => '45–50'],
            ['min' => 38, 'key' => 'excellent',   'name' => 'Excellent',   'range' => '38–44'],
            ['min' => 30, 'key' => 'very_good',   'name' => 'Very Good',   'range' => '30–37'],
            ['min' => 21, 'key' => 'good',         'name' => 'Good',        'range' => '21–29'],
            ['min' => 14, 'key' => 'fair',         'name' => 'Fair',        'range' => '14–20'],
            ['min' => 0,  'key' => 'problematic',  'name' => 'Problematic', 'range' => '0–13'],
        ];
    }

    public function band(float $total): string
    {
        foreach (self::bands() as $band) {
            if ($total >= $band['min']) {
                return $band['name'];
            }
        }

        return 'Problematic';
    }

    public function bandKey(float $total): string
    {
        foreach (self::bands() as $band) {
            if ($total >= $band['min']) {
                return $band['key'];
            }
        }

        return 'problematic';
    }

    /**
     * Tailwind pill classes for each band key, keyed by `bands()['key']` so
     * colors stay correct even if the point thresholds change.
     *
     * @return array<string, string>
     */
    public static function bandColors(): array
    {
        return [
            'outstanding' => 'bg-purple-100 text-purple-800',
            'excellent' => 'bg-green-100 text-green-800',
            'very_good' => 'bg-teal-100 text-teal-800',
            'good' => 'bg-blue-100 text-blue-800',
            'fair' => 'bg-yellow-100 text-yellow-800',
            'problematic' => 'bg-red-100 text-red-800',
        ];
    }

    public static function bandColor(string $bandKey): string
    {
        return self::bandColors()[$bandKey] ?? self::bandColors()['problematic'];
    }
}
