<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Adds every member with a submitted scoresheet to that competition's judge list, so the
     * judge-only scoring gate does not orphan sheets saved before it existed. Skips pairs
     * already present, so a second run inserts nothing.
     */
    public function up(): void
    {
        $missing = DB::table('scoresheets')
            ->join('entries', 'entries.id', '=', 'scoresheets.entry_id')
            ->where('scoresheets.status', 'submitted')
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('competition_judge')
                    ->whereColumn('competition_judge.competition_id', 'entries.competition_id')
                    ->whereColumn('competition_judge.member_id', 'scoresheets.judge_id');
            })
            ->distinct()
            ->get(['entries.competition_id', 'scoresheets.judge_id']);

        foreach ($missing->chunk(500) as $chunk) {
            DB::table('competition_judge')->insert(
                $chunk->map(fn ($row) => [
                    'competition_id' => $row->competition_id,
                    'member_id' => $row->judge_id,
                ])->all()
            );
        }
    }

    public function down(): void
    {
        // The inserted rows cannot be told apart from judges who registered themselves.
    }
};
