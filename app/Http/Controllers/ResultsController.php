<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Member;
use App\Services\CompetitionRankingService;
use Illuminate\Database\Eloquent\Collection;
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
        $viewer = $this->member($request);

        if (! $viewer->is_admin) {
            abort_unless($competition->isClosed(), 403);
        }

        $rankedEntries = $this->ranking->rank($competition);
        $isAdmin = $viewer->is_admin;
        $members = new Collection;
        $judges = new Collection;
        $ownEntry = $competition->entries()->where('member_id', $viewer->id)->first();
        $isJudge = $competition->judges()->whereKey($viewer->id)->exists();

        if ($isAdmin) {
            $members = Member::whereHas('entries', fn ($query) => $query->where('competition_id', $competition->id))
                ->orderBy('name')
                ->get();

            $judges = $competition->judges()->orderBy('name')->get();
        }

        return view('results.index', [
            'competition' => $competition,
            'isAdmin' => $isAdmin,
            'viewerId' => $viewer->id,
            'rankedEntries' => $rankedEntries,
            'members' => $members,
            'judges' => $judges,
            'ownEntry' => $ownEntry,
            'isJudge' => $isJudge,
        ]);
    }
}
