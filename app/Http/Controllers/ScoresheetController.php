<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScoresheetRequest;
use App\Models\Entry;
use App\Models\Scoresheet;

class ScoresheetController extends Controller
{
    public const DESCRIPTORS = [
        ['slug' => 'acetaldehyde', 'name' => 'Acetaldehyde',  'description' => 'Green apple-like aroma and flavor.'],
        ['slug' => 'alcoholic',    'name' => 'Alcoholic',      'description' => 'Aroma, flavor, and warming effect of ethanol and higher alcohols. Sometimes described as hot.'],
        ['slug' => 'astringent',   'name' => 'Astringent',     'description' => 'Puckering, lingering harshness and/or dryness in the finish; harsh graininess; huskiness.'],
        ['slug' => 'diacetyl',     'name' => 'Diacetyl',       'description' => 'Artificial butter, butterscotch, or toffee aroma and flavor. Sometimes perceived as a slickness on the tongue.'],
        ['slug' => 'dms',          'name' => 'DMS',            'description' => 'At low levels a sweet, cooked or canned corn-like aroma and flavor.'],
        ['slug' => 'estery',       'name' => 'Estery',         'description' => 'Aroma and/or flavor of any ester (fruits, fruit flavorings, or roses).'],
        ['slug' => 'grassy',       'name' => 'Grassy',         'description' => 'Aroma/flavor of fresh-cut grass or green leaves.'],
        ['slug' => 'light_struck', 'name' => 'Light-Struck',   'description' => 'Similar to the aroma of a skunk.'],
        ['slug' => 'metallic',     'name' => 'Metallic',       'description' => 'Tinny, coiny, copper, iron, or blood-like flavor.'],
        ['slug' => 'musty',        'name' => 'Musty',          'description' => 'Stale, musty, or moldy aromas/flavors.'],
        ['slug' => 'oxidized',     'name' => 'Oxidized',       'description' => 'Stale, winy/vinous, cardboard, papery, or sherry-like aromas and flavors.'],
        ['slug' => 'phenolic',     'name' => 'Phenolic',       'description' => 'Spicy (clove, pepper), smoky, plastic, or medicinal aromas/flavors.'],
        ['slug' => 'solvent',      'name' => 'Solvent',        'description' => 'Aromas and flavors of higher alcohols (fusel alcohols). Similar to acetone or lacquer thinner.'],
        ['slug' => 'sour_acidic',  'name' => 'Sour/Acidic',   'description' => 'Tartness in aroma and flavor. Can be sharp and clean (lactic), or vinegar-like (acetic).'],
        ['slug' => 'sulfur',       'name' => 'Sulfur',         'description' => 'The aroma of rotten eggs or burning matches.'],
        ['slug' => 'vegetal',      'name' => 'Vegetal',        'description' => 'Cooked, canned, or rotten vegetable aroma and flavor (cabbage, onion, celery, asparagus, etc.)'],
        ['slug' => 'yeasty',       'name' => 'Yeasty',         'description' => 'A bready, sulfury or yeast-like aroma or flavor.'],
    ];

    public static function descriptorSlugs(): array
    {
        return array_column(self::DESCRIPTORS, 'slug');
    }

    public function upsert(StoreScoresheetRequest $request)
    {
        $data = $request->validated();

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
            'entry_id' => $data['entry_id'],
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
            'saved_at' => $sheet->updated_at->toISOString(),
        ]);
    }
}
