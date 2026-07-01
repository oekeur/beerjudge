<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Member;
use App\Services\CompetitionRankingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JudgeReportController extends Controller
{
    public function __construct(private readonly CompetitionRankingService $ranking) {}

    public function show(Competition $competition, Member $judge, Request $request): View
    {
        $viewer = $this->member($request);

        abort_unless($competition->judges()->whereKey($judge->id)->exists(), 404);

        if (! $viewer->is_admin) {
            abort_unless($competition->isClosed(), 403);
            abort_unless($viewer->id === $judge->id, 403);
        }

        return view('results.judge-report', [
            'competition' => $competition,
            'judge' => $judge,
            'comparison' => $this->ranking->judgeComparison($competition, $judge),
            'rankedEntries' => $this->ranking->rank($competition),
        ]);
    }
}
