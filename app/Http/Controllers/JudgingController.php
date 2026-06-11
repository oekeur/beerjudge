<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Scoresheet;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JudgingController extends Controller
{
    public function show(Competition $competition, Request $request): View
    {
        abort_if($competition->status !== 'judging', 403);

        $judge = $this->member($request);

        $entries = $competition->entries()
            ->with(['style', 'member'])
            ->where('member_id', '!=', $judge->id)
            ->orderBy('bottle_number')
            ->get();

        $existingSheets = Scoresheet::where('judge_id', $judge->id)
            ->whereIn('entry_id', $entries->pluck('id'))
            ->get()
            ->keyBy('entry_id');

        $serverData = [
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'bottle_number' => $e->bottle_number,
                'style_code' => $e->style?->code,
                'style_name' => $e->style?->name,
                'brewer_name' => $e->member?->name,
                'style_guide' => [
                    'aroma' => $e->style?->aroma,
                    'appearance' => $e->style?->appearance,
                    'flavor' => $e->style?->flavor,
                    'mouthfeel' => $e->style?->mouthfeel,
                    'overall_impression' => $e->style?->overall_impression,
                ],
            ])->values(),
            'bands' => [
                ['min' => 45, 'name' => __('judging.band.outstanding.name'),  'range' => '45–50', 'description' => __('judging.band.outstanding.description')],
                ['min' => 38, 'name' => __('judging.band.excellent.name'),    'range' => '38–44', 'description' => __('judging.band.excellent.description')],
                ['min' => 30, 'name' => __('judging.band.very_good.name'),    'range' => '30–37', 'description' => __('judging.band.very_good.description')],
                ['min' => 21, 'name' => __('judging.band.good.name'),         'range' => '21–29', 'description' => __('judging.band.good.description')],
                ['min' => 14, 'name' => __('judging.band.fair.name'),         'range' => '14–20', 'description' => __('judging.band.fair.description')],
                ['min' => 0,  'name' => __('judging.band.problematic.name'),  'range' => '0–13',  'description' => __('judging.band.problematic.description')],
            ],
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
        ];

        $descriptors = ScoresheetController::descriptors();
        $isJudge = $competition->judges()->where('member_id', $judge->id)->exists();

        return view('judging.show', compact('competition', 'judge', 'serverData', 'descriptors', 'isJudge'));
    }
}
