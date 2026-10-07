<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Scoresheet;
use App\Services\CompetitionRankingService;
use App\Services\DescriptorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JudgingController extends Controller
{
    public function show(Competition $competition, Request $request): View
    {
        abort_unless($competition->isJudging(), 403);

        $judge = $this->member($request);

        $entries = $competition->entries()
            ->with(['style', 'member'])
            ->where('member_id', '!=', $judge->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $existingSheets = Scoresheet::where('judge_id', $judge->id)
            ->whereIn('entry_id', $entries->pluck('id'))
            ->get()
            ->keyBy('entry_id');

        $serverData = [
            'isAdmin' => $judge->is_admin,
            'progressUrl' => route('judging.progress', $competition),
            'entries' => $entries->map(fn ($e, $i) => [
                'id' => $e->id,
                'tasting_number' => $i + 1,
                'style_code' => $e->style?->code,
                'style_name' => $e->style?->name,
                'brewer_name' => $e->member?->name,
                'srm_min' => $e->style?->srm_min,
                'srm_max' => $e->style?->srm_max,
                'style_guide' => [
                    'aroma' => $e->style?->aroma,
                    'appearance' => $e->style?->appearance,
                    'flavor' => $e->style?->flavor,
                    'mouthfeel' => $e->style?->mouthfeel,
                    'overall_impression' => $e->style?->overall_impression,
                ],
                'style_info' => [
                    'category_name' => $e->style?->category_name,
                    'category_description' => $e->style?->category_description,
                    'history' => $e->style?->history,
                    'style_comparison' => $e->style?->style_comparison,
                ],
            ])->values(),
            'bands' => array_map(fn (array $band) => [
                'min' => $band['min'],
                'name' => __("judging.band.{$band['key']}.name"),
                'range' => $band['range'],
                'description' => __("judging.band.{$band['key']}.description"),
            ], CompetitionRankingService::bands()),
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

        if ($request->boolean('register')) {
            $competition->judges()->syncWithoutDetaching([$judge->id]);
        }

        $descriptors = DescriptorService::descriptors();
        $isJudge = $competition->judges()->where('member_id', $judge->id)->exists();

        return view('judging.show', [
            'competition' => $competition,
            'judge' => $judge,
            'serverData' => $serverData,
            'descriptors' => $descriptors,
            'isJudge' => $isJudge,
        ]);
    }

    public function progress(Competition $competition, Request $request): JsonResponse
    {
        abort_unless($competition->isJudging(), 403);

        $member = $this->member($request);
        abort_unless($member->is_admin, 403);

        $judgeIds = $competition->judges()->pluck('member_id');
        $entries = $competition->entries()->orderBy('sort_order')->orderBy('id')->get(['id', 'member_id']);

        $submittedCounts = Scoresheet::whereIn('entry_id', $entries->pluck('id'))
            ->where('status', 'submitted')
            ->selectRaw('entry_id, count(*) as cnt')
            ->groupBy('entry_id')
            ->pluck('cnt', 'entry_id');

        $progress = [];
        foreach ($entries as $entry) {
            $submitted = $submittedCounts[$entry->id] ?? 0;
            $progress[$entry->id] = [
                'submitted' => is_numeric($submitted) ? (int) $submitted : 0,
                'total' => $judgeIds->reject(fn ($id) => $id === $entry->member_id)->count(),
            ];
        }

        return response()->json($progress);
    }
}
