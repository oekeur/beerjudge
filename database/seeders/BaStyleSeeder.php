<?php

namespace Database\Seeders;

use App\Models\Style;
use Illuminate\Database\Seeder;

class BaStyleSeeder extends Seeder
{
    /** Overridden by tests to point at a fixture. Defaults to the committed snapshot. */
    public ?string $path = null;

    public function run(): void
    {
        $contents = file_get_contents($this->path ?? base_path('ba_styleguide-2026.json'));
        if ($contents === false) {
            return;
        }

        $json = json_decode($contents, true);
        if (! is_array($json)) {
            return;
        }

        $stylesData = $json['styles'] ?? null;
        if (! is_array($stylesData)) {
            return;
        }

        $edition = is_scalar($json['edition'] ?? null) ? (string) $json['edition'] : '2026';

        $number = fn (array $s, string $key): mixed => is_numeric($s[$key] ?? null) ? $s[$key] : null;

        $styles = [];
        foreach ($stylesData as $s) {
            if (! is_array($s) || ! isset($s['code'], $s['name'])) {
                continue;
            }

            $styles[] = [
                'code' => $s['code'],
                'source' => 'ba_beer',
                'category_name' => $s['category'] ?? '',
                'category_description' => null,
                'name' => $s['name'],
                'version' => $edition,
                'og_min' => $number($s, 'og_min'),
                'og_max' => $number($s, 'og_max'),
                'fg_min' => $number($s, 'fg_min'),
                'fg_max' => $number($s, 'fg_max'),
                'abv_min' => $number($s, 'abv_min'),
                'abv_max' => $number($s, 'abv_max'),
                'ibu_min' => $number($s, 'ibu_min'),
                'ibu_max' => $number($s, 'ibu_max'),
                'srm_min' => $number($s, 'srm_min'),
                'srm_max' => $number($s, 'srm_max'),
                'overall_impression' => $s['overall_impression'] ?? null,
                'aroma' => $s['aroma'] ?? null,
                'appearance' => $s['appearance'] ?? null,
                'flavor' => $s['flavor'] ?? null,
                'mouthfeel' => $s['mouthfeel'] ?? null,
                'comments' => null,
                'history' => null,
                'style_comparison' => null,
                'ingredients' => null,
                'examples' => null,
                'tags' => null,
            ];
        }

        Style::upsert(
            $styles,
            ['code', 'source'],
            [
                'category_name', 'category_description', 'name', 'version',
                'og_min', 'og_max', 'fg_min', 'fg_max',
                'abv_min', 'abv_max', 'ibu_min', 'ibu_max', 'srm_min', 'srm_max',
                'overall_impression', 'aroma', 'appearance', 'flavor', 'mouthfeel',
                'comments', 'history', 'style_comparison', 'ingredients', 'examples', 'tags',
            ]
        );
    }
}
