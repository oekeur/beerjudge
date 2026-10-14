<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Scoresheet;
use App\Models\Style;
use App\Services\CompetitionRankingService;
use App\Services\DescriptorService;
use App\Services\JudgingProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JudgingController extends Controller
{
    public function show(Competition $competition, Request $request): View
    {
        abort_unless($competition->isJudging(), 403);

        $judge = $this->member($request);

        if ($request->boolean('register')) {
            $competition->judges()->syncWithoutDetaching([$judge->id]);
        }

        $isJudge = $competition->judges()->where('member_id', $judge->id)->exists();

        // Number the whole tasting order before removing the judge's own entries, so every
        // judge sees the same number for the same beer. Own entries stay visible as locked rows.
        $tastingOrder = $competition->entries()
            ->with(['style', 'member'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();
        $tastingNumbers = $tastingOrder->mapWithKeys(fn ($e, $i) => [$e->id => $i + 1]);
        $isOwn = fn (Entry $e): bool => $e->member_id === $judge->id;

        // Only registered judges get the scoring data; everyone else sees the participate prompt.
        $ownEntries = $tastingOrder->filter(fn (Entry $e): bool => $isJudge && $isOwn($e))->values();
        $entries = $tastingOrder->filter(fn (Entry $e): bool => $isJudge && ! $isOwn($e))->values();

        $existingSheets = Scoresheet::where('judge_id', $judge->id)
            ->whereIn('entry_id', $entries->pluck('id'))
            ->get()
            ->keyBy('entry_id');

        $serverData = [
            'competitionId' => $competition->id,
            'isAdmin' => $judge->is_admin,
            'progressUrl' => route('judging.progress', $competition),
            'progressEntries' => $judge->is_admin
                ? $tastingOrder->map(fn ($e) => [
                    'id' => $e->id,
                    'tasting_number' => $tastingNumbers[$e->id],
                    'brewer_name' => $e->member?->name,
                ])->values()
                : [],
            'entries' => $entries->map(fn ($e) => [
                'id' => $e->id,
                'tasting_number' => $tastingNumbers[$e->id],
                'style_code' => $e->style?->code,
                'style_name' => $e->style?->name,
                'style_source' => $e->style?->source,
                'style_attribution' => Style::attributionFor($e->style?->source),
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
            'ownEntries' => $ownEntries->map(fn ($e) => [
                'id' => $e->id,
                'tasting_number' => $tastingNumbers[$e->id],
                'style_name' => $e->style?->name,
                'is_own' => true,
            ])->values(),
            'bands' => array_map(fn (array $band) => [
                'min' => $band['min'],
                'name' => __("judging.band.{$band['key']}.name"),
                'range' => $band['range'],
                'description' => __("judging.band.{$band['key']}.description"),
            ], CompetitionRankingService::bands()),
            'existingSheets' => $existingSheets->map(fn (Scoresheet $s) => $s->toJudgingPayload())->values(),
        ];

        $descriptors = DescriptorService::descriptors();

        return view('judging.show', [
            'competition' => $competition,
            'judge' => $judge,
            'serverData' => $serverData,
            'descriptors' => $descriptors,
            'isJudge' => $isJudge,
        ]);
    }

    public function progress(Competition $competition, Request $request, JudgingProgressService $progressService): JsonResponse
    {
        abort_unless($competition->isJudging(), 403);

        $member = $this->member($request);
        abort_unless($member->is_admin, 403);

        $progress = collect($progressService->forCompetition($competition))
            ->map(fn (array $row) => ['submitted' => $row['submitted'], 'total' => $row['eligible']])
            ->all();

        return response()->json($progress);
    }
}
