<?php

namespace App\Http\Controllers;

use App\Models\Entry;
use App\Models\Scoresheet;
use Illuminate\Http\Request;

class ScoresheetController extends Controller
{
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'client_uuid'      => ['required', 'uuid'],
            'entry_id'         => ['required', 'integer', 'exists:entries,id'],
            'aroma'            => ['required', 'integer', 'min:0', 'max:12'],
            'appearance'       => ['required', 'integer', 'min:0', 'max:3'],
            'flavour'          => ['required', 'integer', 'min:0', 'max:20'],
            'mouthfeel'        => ['required', 'integer', 'min:0', 'max:5'],
            'overall'          => ['required', 'integer', 'min:0', 'max:10'],
            'aroma_notes'      => ['nullable', 'string', 'max:2000'],
            'appearance_notes' => ['nullable', 'string', 'max:2000'],
            'flavour_notes'    => ['nullable', 'string', 'max:2000'],
            'mouthfeel_notes'  => ['nullable', 'string', 'max:2000'],
            'overall_notes'    => ['nullable', 'string', 'max:2000'],
            'status'           => ['required', 'in:draft,submitted'],
        ]);

        $judge = $request->user();
        $entry = Entry::with('competition')->findOrFail($data['entry_id']);

        abort_if($entry->competition->status !== 'judging', 422, 'Competition is not in judging phase.');
        abort_if($entry->member_id === $judge->id, 422, 'You cannot score your own entry.');

        // Look up existing sheet: by client_uuid first, then by (entry_id, judge_id)
        $sheet = Scoresheet::where('client_uuid', $data['client_uuid'])->first()
            ?? Scoresheet::where('entry_id', $data['entry_id'])
                         ->where('judge_id', $judge->id)
                         ->first();

        $attributes = [
            'entry_id'         => $data['entry_id'],
            'judge_id'         => $judge->id,
            'aroma'            => $data['aroma'],
            'appearance'       => $data['appearance'],
            'flavour'          => $data['flavour'],
            'mouthfeel'        => $data['mouthfeel'],
            'overall'          => $data['overall'],
            'aroma_notes'      => $data['aroma_notes'] ?? null,
            'appearance_notes' => $data['appearance_notes'] ?? null,
            'flavour_notes'    => $data['flavour_notes'] ?? null,
            'mouthfeel_notes'  => $data['mouthfeel_notes'] ?? null,
            'overall_notes'    => $data['overall_notes'] ?? null,
            'client_uuid'      => $data['client_uuid'],
            'status'           => $data['status'],
            'submitted_at'     => $data['status'] === 'submitted' ? now() : null,
        ];

        if ($sheet) {
            $sheet->update($attributes);
        } else {
            $sheet = Scoresheet::create($attributes);
        }

        return response()->json([
            'id'          => $sheet->id,
            'client_uuid' => $sheet->client_uuid,
            'total'       => $sheet->total,
            'status'      => $sheet->status,
            'saved_at'    => $sheet->updated_at->toISOString(),
        ]);
    }
}
