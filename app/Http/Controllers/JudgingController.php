<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Scoresheet;
use Illuminate\Http\Request;

class JudgingController extends Controller
{
    public function show(Competition $competition, Request $request)
    {
        abort_if($competition->status !== 'judging', 403);

        $judge = $request->user();

        $entries = $competition->entries()
            ->with('style')
            ->where('member_id', '!=', $judge->id)
            ->orderBy('bottle_number')
            ->get();

        $existingSheets = Scoresheet::where('judge_id', $judge->id)
            ->whereIn('entry_id', $entries->pluck('id'))
            ->get()
            ->keyBy('entry_id');

        $serverDataJson = json_encode([
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'bottle_number' => $e->bottle_number,
                'name' => $e->name,
                'style_code' => $e->style->code,
                'style_name' => $e->style->name,
            ])->values(),
            'existingSheets' => $existingSheets->map(fn ($s) => [
                'entry_id' => $s->entry_id,
                'client_uuid' => $s->client_uuid,
                'aroma' => $s->aroma,
                'appearance' => $s->appearance,
                'flavour' => $s->flavour,
                'mouthfeel' => $s->mouthfeel,
                'overall' => $s->overall,
                'aroma_notes' => $s->aroma_notes ?? '',
                'appearance_notes' => $s->appearance_notes ?? '',
                'flavour_notes' => $s->flavour_notes ?? '',
                'mouthfeel_notes' => $s->mouthfeel_notes ?? '',
                'overall_notes' => $s->overall_notes ?? '',
                'descriptors' => $s->descriptors ?? [],
                'status' => $s->status,
            ])->values(),
        ]);

        $descriptors = ScoresheetController::DESCRIPTORS;

        return view('judging.show', compact('competition', 'entries', 'existingSheets', 'judge', 'serverDataJson', 'descriptors'));
    }
}
