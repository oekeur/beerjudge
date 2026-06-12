<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        /** @var Collection<string, \Illuminate\Database\Eloquent\Collection<int, Competition>> $competitions */
        $competitions = Competition::where('status', '!=', Competition::STATUS_DRAFT)
            ->orderByRaw("CASE status WHEN 'judging' THEN 0 WHEN 'open' THEN 1 WHEN 'closed' THEN 2 ELSE 3 END")
            ->orderBy('judging_date')
            ->get()
            ->groupBy('status');

        return view('welcome', compact('competitions'));
    }
}
