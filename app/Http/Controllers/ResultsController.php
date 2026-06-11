<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Scoresheet;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function show(Competition $competition, Member $member, Request $request): View
    {
        abort_unless($competition->isClosed(), 403);

        $viewer = $request->user();
        abort_if(! $viewer->is_admin && $viewer->id !== $member->id, 403);

        $rankedEntries = $this->buildRankedEntries($competition);
        $memberEntries = $rankedEntries
            ->filter(fn (array $row): bool => $row['entry']->member_id === $member->id)
            ->values();

        return view('results.show', compact('competition', 'member', 'rankedEntries', 'memberEntries'));
    }

    public function index(Competition $competition, Request $request): View
    {
        abort_unless($request->user()->is_admin, 403);
        abort_unless($competition->isClosed(), 403);

        $members = Member::whereHas('entries', fn ($query) => $query->where('competition_id', $competition->id))
            ->orderBy('name')
            ->get();

        return view('results.index', compact('competition', 'members'));
    }

    /**
     * @return Collection<int, array{entry: Entry, sheets: \Illuminate\Database\Eloquent\Collection<int, Scoresheet>, avg_total: float, avg_overall: float, sheet_count: int, band: string, placement: int}>
     */
    private function buildRankedEntries(Competition $competition): Collection
    {
        $entries = Entry::where('competition_id', $competition->id)
            ->with(['member', 'style', 'scoresheets' => fn ($query) => $query->where('status', 'submitted')->with('judge')])
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

    private function band(float $total): string
    {
        return match (true) {
            $total >= 45 => 'Outstanding',
            $total >= 38 => 'Excellent',
            $total >= 30 => 'Very Good',
            $total >= 21 => 'Good',
            $total >= 14 => 'Fair',
            default => 'Problematic',
        };
    }
}
