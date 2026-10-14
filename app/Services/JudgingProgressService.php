<?php

namespace App\Services;

use App\Enums\ScoresheetStatus;
use App\Models\Competition;
use App\Models\Member;
use App\Models\Scoresheet;

class JudgingProgressService
{
    /**
     * Per-entry progress keyed by entry id, in tasting order.
     *
     * Eligible judges are the current judges plus anyone with a submitted sheet on the entry
     * (a judge who left keeps their sheet counting), minus the brewer. Missing lists only
     * current judges without a submitted sheet, so a leaver is never chased.
     *
     * @return array<int, array{submitted: int, eligible: int, missing: list<int>}>
     */
    public function forCompetition(Competition $competition): array
    {
        $judgeIds = $competition->judges->map(fn (Member $judge): int => $judge->id)->all();
        $entries = $competition->entries()->orderBy('sort_order')->orderBy('id')->get(['id', 'member_id']);

        $submittedSheets = Scoresheet::whereIn('entry_id', $entries->pluck('id'))
            ->where('status', ScoresheetStatus::Submitted->value)
            ->get(['entry_id', 'judge_id']);

        $progress = [];
        foreach ($entries as $entry) {
            $submittedIds = $submittedSheets->where('entry_id', $entry->id)
                ->map(fn (Scoresheet $sheet): int => $sheet->judge_id)
                ->unique()
                ->reject(fn (int $id) => $id === $entry->member_id)
                ->values()
                ->all();
            $currentIds = array_values(array_filter($judgeIds, fn (int $id) => $id !== $entry->member_id));

            $progress[$entry->id] = [
                'submitted' => count($submittedIds),
                'eligible' => count(array_unique([...$currentIds, ...$submittedIds])),
                'missing' => array_values(array_diff($currentIds, $submittedIds)),
            ];
        }

        return $progress;
    }
}
