<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JudgeRegistrationController extends Controller
{
    public function store(Competition $competition, Request $request): RedirectResponse
    {
        abort_if(
            ! in_array($competition->status, [CompetitionStatus::Open, CompetitionStatus::Judging]),
            403,
        );

        $competition->judges()->syncWithoutDetaching([$this->member($request)->id]);

        return back();
    }

    public function destroy(Competition $competition, Request $request): RedirectResponse
    {
        $competition->judges()->detach($this->member($request)->id);

        return back();
    }
}
