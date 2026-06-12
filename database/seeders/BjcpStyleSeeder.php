<?php

namespace Database\Seeders;

use App\Models\Style;
use Illuminate\Database\Seeder;

class BjcpStyleSeeder extends Seeder
{
    public function run(): void
    {
        $json = json_decode(
            file_get_contents(base_path('bjcp_styleguide-2021.json')),
            true
        );

        $styles = array_map(function (array $s) {
            return [
                'code' => $s['style_id'],
                'category_name' => $s['category'],
                'category_description' => $s['category_description'] ?? null,
                'name' => $s['name'],
                'version' => '2021',
                'og_min' => $s['original_gravity']['minimum']['value'] ?? null,
                'og_max' => $s['original_gravity']['maximum']['value'] ?? null,
                'fg_min' => $s['final_gravity']['minimum']['value'] ?? null,
                'fg_max' => $s['final_gravity']['maximum']['value'] ?? null,
                'abv_min' => $s['alcohol_by_volume']['minimum']['value'] ?? null,
                'abv_max' => $s['alcohol_by_volume']['maximum']['value'] ?? null,
                'ibu_min' => $s['international_bitterness_units']['minimum']['value'] ?? null,
                'ibu_max' => $s['international_bitterness_units']['maximum']['value'] ?? null,
                'srm_min' => $s['color']['minimum']['value'] ?? null,
                'srm_max' => $s['color']['maximum']['value'] ?? null,
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
        }, $json['beerjson']['styles']);

        Style::upsert(
            $styles,
            ['code'],
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
