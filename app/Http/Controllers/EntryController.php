<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEntryRequest;
use App\Models\Competition;
use App\Models\Entry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EntryController extends Controller
{
    public function create(Competition $competition, Request $request): View
    {
        abort_unless($competition->status === Competition::STATUS_OPEN, 403);

        $member = $this->member($request);
        $styles = $competition->styles()->orderBy('code')->get();
        $myEntries = $competition->entries()->with('style')->where('member_id', $member->id)->get();

        return view('entries.create', compact('competition', 'styles', 'myEntries'));
    }

    public function store(Competition $competition, StoreEntryRequest $request): RedirectResponse
    {
        abort_unless($competition->status === Competition::STATUS_OPEN, 403);

        Entry::create([
            'competition_id' => $competition->id,
            'member_id' => $this->member($request)->id,
            'style_id' => $request->validated('style_id'),
            'brewer_notes' => $request->validated('brewer_notes'),
        ]);

        return redirect()
            ->route('competitions.entries.create', $competition)
            ->with('success', __('entry.submitted'));
    }
}
