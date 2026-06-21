<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Member;
use App\Services\CompetitionRankingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ResultsController extends Controller
{
    public function __construct(private readonly CompetitionRankingService $ranking) {}

    public function show(Competition $competition, Member $member, Request $request): View
    {
        $viewer = $this->member($request);

        if (! $viewer->is_admin) {
            abort_unless($competition->isClosed(), 403);
            abort_unless($viewer->id === $member->id, 403);
        }

        $rankedEntries = $this->ranking->rank($competition);
        $memberEntries = $rankedEntries
            ->filter(fn (array $row): bool => $row['entry']->member_id === $member->id)
            ->values();

        return view('results.show', [
            'competition' => $competition,
            'member' => $member,
            'rankedEntries' => $rankedEntries,
            'memberEntries' => $memberEntries,
        ]);
    }

    public function index(Competition $competition, Request $request): View
    {
        if (! $this->member($request)->is_admin) {
            abort_unless($competition->isClosed(), 403);
        }

        $members = Member::whereHas('entries', fn ($query) => $query->where('competition_id', $competition->id))
            ->orderBy('name')
            ->get();

        return view('results.index', compact('competition', 'members'));
    }
}
