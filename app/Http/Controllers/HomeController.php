<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $competitions = Competition::where('status', '!=', Competition::STATUS_DRAFT)
            ->orderByDesc('judging_date')
            ->get();

        return view('welcome', compact('competitions'));
    }
}
