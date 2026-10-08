<?php

namespace App\Http\Controllers;

use App\Enums\ScoresheetStatus;
use App\Http\Requests\StoreScoresheetRequest;
use App\Models\Entry;
use App\Models\Scoresheet;
use Illuminate\Http\JsonResponse;

class ScoresheetController extends Controller
{
    public function upsert(StoreScoresheetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $entryId = $request->integer('entry_id');

        $judge = $this->member($request);
        $entry = Entry::with('competition')->findOrFail($entryId);

        abort_unless($entry->competition?->isJudging() === true, 422, 'Competition is not in judging phase.');
        abort_if($entry->member_id === $judge->id, 422, 'You cannot score your own entry.');
        abort_if(
            Scoresheet::where('client_uuid', $data['client_uuid'])->where('judge_id', '!=', $judge->id)->exists(),
            422,
            'UUID already in use by another judge.'
        );

        // Look up existing sheet: by client_uuid first, then by (entry_id, judge_id)
        $sheet = Scoresheet::where('client_uuid', $data['client_uuid'])
            ->where('judge_id', $judge->id)
            ->first()
            ?? Scoresheet::where('entry_id', $entryId)
                ->where('judge_id', $judge->id)
                ->first();

        // A request delayed in transit can arrive after a newer save of the same sheet. A payload
        // whose revision is not above the stored one is ignored and answered with the stored sheet,
        // so the client converges. Payloads without a revision (older clients) always apply.
        $revision = $data['client_revision'] ?? null;
        $isStale = $sheet !== null
            && $revision !== null
            && $sheet->client_revision !== null
            && $revision <= $sheet->client_revision;

        $attributes = [
            'entry_id' => $entryId,
            'judge_id' => $judge->id,
            'aroma' => $data['aroma'],
            'appearance' => $data['appearance'],
            'flavour' => $data['flavour'],
            'mouthfeel' => $data['mouthfeel'],
            'overall' => $data['overall'],
            'aroma_notes' => $data['aroma_notes'] ?? null,
            'appearance_notes' => $data['appearance_notes'] ?? null,
            'flavour_notes' => $data['flavour_notes'] ?? null,
            'mouthfeel_notes' => $data['mouthfeel_notes'] ?? null,
            'overall_notes' => $data['overall_notes'] ?? null,
            'descriptors' => $data['descriptors'] ?? [],
            'client_uuid' => $data['client_uuid'],
            'client_revision' => $revision ?? $sheet?->client_revision,
            'status' => $data['status'],
            'submitted_at' => $data['status'] === ScoresheetStatus::Submitted->value ? now() : null,
        ];

        if (! $isStale) {
            if ($sheet) {
                $sheet->update($attributes);
            } else {
                $sheet = Scoresheet::create($attributes);
            }
        }

        return response()->json([
            'id' => $sheet->id,
            'client_uuid' => $sheet->client_uuid,
            'client_revision' => $sheet->client_revision,
            'total' => $sheet->total,
            'status' => $sheet->status,
            'saved_at' => $sheet->updated_at?->toISOString(),
            'ignored' => $isStale,
            'sheet' => $sheet->toJudgingPayload(),
        ]);
    }
}
