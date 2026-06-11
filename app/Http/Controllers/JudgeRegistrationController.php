<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class JudgeRegistrationController extends Controller
{
    public function store(Competition $competition, Request $request): RedirectResponse
    {
        abort_if(
            ! in_array($competition->status, [Competition::STATUS_OPEN, Competition::STATUS_JUDGING]),
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
