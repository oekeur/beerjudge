<?php

namespace App\Http\Controllers;

use App\Enums\CompetitionStatus;
use App\Models\Competition;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        /** @var Collection<string, \Illuminate\Database\Eloquent\Collection<int, Competition>> $competitions */
        $competitions = Competition::where('status', '!=', CompetitionStatus::Draft)
            ->orderByRaw('CASE status WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END', [
                CompetitionStatus::Judging->value,
                CompetitionStatus::Open->value,
                CompetitionStatus::Closed->value,
            ])
            ->orderBy('judging_date')
            ->get()
            ->groupBy('status');

        return view('welcome', compact('competitions'));
    }
}
