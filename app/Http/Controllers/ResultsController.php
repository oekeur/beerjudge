<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    public function show(Competition $competition, Member $member, Request $request)
    {
        abort_if($competition->status !== 'closed', 403);

        $viewer = $request->user();
        if (!$viewer->is_admin) {
            abort_if($competition->published_at === null, 403);
            abort_if($viewer->id !== $member->id, 403);
        }

        $rankedEntries = $this->buildRankedEntries($competition);
        $memberEntries = $rankedEntries->filter(fn($row) => $row['entry']->member_id === $member->id)->values();

        return view('results.show', compact('competition', 'member', 'rankedEntries', 'memberEntries'));
    }

    public function index(Competition $competition, Request $request)
    {
        abort_if(!$request->user()->is_admin, 403);
        abort_if($competition->status !== 'closed', 403);

        $members = Member::whereHas('entries', fn($q) => $q->where('competition_id', $competition->id))
            ->orderBy('name')
            ->get();

        return view('results.index', compact('competition', 'members'));
    }

    private function buildRankedEntries(Competition $competition): \Illuminate\Support\Collection
    {
        $entries = Entry::where('competition_id', $competition->id)
            ->with(['member', 'style', 'scoresheets' => fn($q) => $q->where('status', 'submitted')->with('judge')])
            ->get();

        $rows = $entries->map(function (Entry $entry) {
            $sheets = $entry->scoresheets;
            $avgTotal   = $sheets->count() > 0 ? $sheets->avg('total') : 0;
            $avgOverall = $sheets->count() > 0 ? $sheets->avg('overall') : 0;
            return [
                'entry'       => $entry,
                'sheets'      => $sheets,
                'avg_total'   => round((float) $avgTotal, 2),
                'avg_overall' => round((float) $avgOverall, 2),
                'sheet_count' => $sheets->count(),
                'band'        => $this->band((float) $avgTotal),
            ];
        });

        $ranked = $rows->sort(fn($a, $b) =>
            $b['avg_total'] <=> $a['avg_total'] ?: $b['avg_overall'] <=> $a['avg_overall']
        )->values();

        return $ranked->map(function ($row, $i) {
            $row['placement'] = $i + 1;
            return $row;
        });
    }

    private function band(float $total): string
    {
        return match(true) {
            $total >= 45 => 'Outstanding',
            $total >= 38 => 'Excellent',
            $total >= 30 => 'Very Good',
            $total >= 21 => 'Good',
            $total >= 14 => 'Fair',
            default      => 'Problematic',
        };
    }
}
