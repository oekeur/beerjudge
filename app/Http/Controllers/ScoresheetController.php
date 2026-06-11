<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoresheetRequest;
use App\Models\Entry;
use App\Models\Scoresheet;
use Illuminate\Http\JsonResponse;

class ScoresheetController extends Controller
{
    public const DESCRIPTOR_SLUGS = [
        'acetaldehyde', 'alcoholic', 'astringent', 'diacetyl', 'dms',
        'estery', 'grassy', 'light_struck', 'metallic', 'musty',
        'oxidized', 'phenolic', 'solvent', 'sour_acidic', 'sulfur',
        'vegetal', 'yeasty',
    ];

    /**
     * @return array<int, array{slug: string, name: string, description: string}>
     */
    public static function descriptors(): array
    {
        return array_map(fn (string $slug) => [
            'slug' => $slug,
            'name' => __("descriptor.{$slug}.name"),
            'description' => __("descriptor.{$slug}.description"),
        ], self::DESCRIPTOR_SLUGS);
    }

    /**
     * @return array<int, string>
     */
    public static function descriptorSlugs(): array
    {
        return self::DESCRIPTOR_SLUGS;
    }

    public function upsert(StoreScoresheetRequest $request): JsonResponse
    {
        $data = $request->validated();
        $entryId = $request->integer('entry_id');

        $judge = $this->member($request);
        $entry = Entry::with('competition')->findOrFail($entryId);

        abort_if($entry->competition?->status !== 'judging', 422, 'Competition is not in judging phase.');
        abort_if($entry->member_id === $judge->id, 422, 'You cannot score your own entry.');

        // Look up existing sheet: by client_uuid first, then by (entry_id, judge_id)
        $sheet = Scoresheet::where('client_uuid', $data['client_uuid'])->first()
            ?? Scoresheet::where('entry_id', $entryId)
                ->where('judge_id', $judge->id)
                ->first();

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
            'status' => $data['status'],
            'submitted_at' => $data['status'] === 'submitted' ? now() : null,
        ];

        if ($sheet) {
            $sheet->update($attributes);
        } else {
            $sheet = Scoresheet::create($attributes);
        }

        return response()->json([
            'id' => $sheet->id,
            'client_uuid' => $sheet->client_uuid,
            'total' => $sheet->total,
            'status' => $sheet->status,
            'saved_at' => $sheet->updated_at?->toISOString(),
        ]);
    }
}
