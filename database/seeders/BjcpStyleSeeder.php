<?php

namespace Database\Seeders;

use App\Models\Style;
use Illuminate\Database\Seeder;

class BjcpStyleSeeder extends Seeder
{
    public function run(): void
    {
        $contents = file_get_contents(base_path('bjcp_styleguide-2021.json'));
        if ($contents === false) {
            return;
        }

        $json = json_decode($contents, true);
        if (! is_array($json)) {
            return;
        }

        $beerjson = $json['beerjson'] ?? null;
        if (! is_array($beerjson)) {
            return;
        }

        $stylesData = $beerjson['styles'] ?? null;
        if (! is_array($stylesData)) {
            return;
        }

        $styles = array_map(function (mixed $s) {
            if (! is_array($s)) {
                return [];
            }

            $og = is_array($s['original_gravity'] ?? null) ? $s['original_gravity'] : [];
            $fg = is_array($s['final_gravity'] ?? null) ? $s['final_gravity'] : [];
            $abv = is_array($s['alcohol_by_volume'] ?? null) ? $s['alcohol_by_volume'] : [];
            $ibu = is_array($s['international_bitterness_units'] ?? null) ? $s['international_bitterness_units'] : [];
            $srm = is_array($s['color'] ?? null) ? $s['color'] : [];

            $ogMin = is_array($og['minimum'] ?? null) ? ($og['minimum']['value'] ?? null) : null;
            $ogMax = is_array($og['maximum'] ?? null) ? ($og['maximum']['value'] ?? null) : null;
            $fgMin = is_array($fg['minimum'] ?? null) ? ($fg['minimum']['value'] ?? null) : null;
            $fgMax = is_array($fg['maximum'] ?? null) ? ($fg['maximum']['value'] ?? null) : null;
            $abvMin = is_array($abv['minimum'] ?? null) ? ($abv['minimum']['value'] ?? null) : null;
            $abvMax = is_array($abv['maximum'] ?? null) ? ($abv['maximum']['value'] ?? null) : null;
            $ibuMin = is_array($ibu['minimum'] ?? null) ? ($ibu['minimum']['value'] ?? null) : null;
            $ibuMax = is_array($ibu['maximum'] ?? null) ? ($ibu['maximum']['value'] ?? null) : null;
            $srmMin = is_array($srm['minimum'] ?? null) ? ($srm['minimum']['value'] ?? null) : null;
            $srmMax = is_array($srm['maximum'] ?? null) ? ($srm['maximum']['value'] ?? null) : null;

            return [
                'code' => $s['style_id'],
                'source' => 'bjcp_beer',
                'category_name' => $s['category'],
                'category_description' => $s['category_description'] ?? null,
                'name' => $s['name'],
                'version' => '2021',
                'og_min' => is_numeric($ogMin) ? $ogMin : null,
                'og_max' => is_numeric($ogMax) ? $ogMax : null,
                'fg_min' => is_numeric($fgMin) ? $fgMin : null,
                'fg_max' => is_numeric($fgMax) ? $fgMax : null,
                'abv_min' => is_numeric($abvMin) ? $abvMin : null,
                'abv_max' => is_numeric($abvMax) ? $abvMax : null,
                'ibu_min' => is_numeric($ibuMin) ? $ibuMin : null,
                'ibu_max' => is_numeric($ibuMax) ? $ibuMax : null,
                'srm_min' => is_numeric($srmMin) ? $srmMin : null,
                'srm_max' => is_numeric($srmMax) ? $srmMax : null,
                'overall_impression' => $s['overall_impression'] ?? null,
                'aroma' => $s['aroma'] ?? null,
                'appearance' => $s['appearance'] ?? null,
                'flavor' => $s['flavor'] ?? null,
                'mouthfeel' => $s['mouthfeel'] ?? null,
                'comments' => $s['comments'] ?? null,
                'history' => $s['history'] ?? null,
                'style_comparison' => $s['style_comparison'] ?? null,
                'ingredients' => $s['ingredients'] ?? null,
                'examples' => $s['examples'] ?? null,
                'tags' => $s['tags'] ?? null,
            ];
        }, $stylesData);

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
