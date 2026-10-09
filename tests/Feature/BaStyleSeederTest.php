<?php

namespace Tests\Feature;

use App\Models\Style;
use Database\Seeders\BaStyleSeeder;
use Database\Seeders\BjcpStyleSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BaStyleSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixturePath = sys_get_temp_dir().'/ba-seeder-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->fixturePath)) {
            unlink($this->fixturePath);
        }

        parent::tearDown();
    }

    private function seedFixture(): void
    {
        file_put_contents($this->fixturePath, json_encode([
            'edition' => '2026',
            'source_url' => 'https://example.test',
            'scraped_at' => '2026-10-09',
            'styles' => [
                [
                    'code' => 'BA-001', 'name' => 'Ordinary Bitter', 'category' => 'British Origin Ale Styles',
                    'appearance' => "Color: Gold\nClarity: Chill haze", 'aroma' => 'Malt: Low',
                    'flavor' => 'Bitterness: Medium', 'mouthfeel' => 'Body: Low', 'overall_impression' => 'Notes: Drinkable',
                    'og_min' => 1.033, 'og_max' => 1.038, 'fg_min' => 1.006, 'fg_max' => 1.012,
                    'abv_min' => 3.0, 'abv_max' => 4.2, 'ibu_min' => 20, 'ibu_max' => 35, 'srm_min' => 5, 'srm_max' => 12,
                ],
                [
                    'code' => 'BA-002', 'name' => 'Wood Aged', 'category' => 'Other',
                    'appearance' => null, 'aroma' => null, 'flavor' => null, 'mouthfeel' => null, 'overall_impression' => null,
                    'og_min' => null, 'og_max' => null, 'fg_min' => null, 'fg_max' => null,
                    'abv_min' => null, 'abv_max' => null, 'ibu_min' => null, 'ibu_max' => null, 'srm_min' => null, 'srm_max' => null,
                ],
            ],
        ]));

        $seeder = new BaStyleSeeder;
        $seeder->path = $this->fixturePath;
        $seeder->run();
    }

    public function test_it_maps_json_fields_to_style_columns(): void
    {
        $this->seedFixture();

        $style = Style::where('code', 'BA-001')->where('source', 'ba_beer')->firstOrFail();
        $this->assertSame('2026', $style->version);
        $this->assertSame('Ordinary Bitter', $style->name);
        $this->assertSame('British Origin Ale Styles', $style->category_name);
        $this->assertSame("Color: Gold\nClarity: Chill haze", $style->appearance);
        $this->assertSame('Malt: Low', $style->aroma);
        $this->assertSame('Bitterness: Medium', $style->flavor);
        $this->assertSame('Body: Low', $style->mouthfeel);
        $this->assertSame('Notes: Drinkable', $style->overall_impression);
        $this->assertSame(1.033, $style->og_min);
        $this->assertSame(1.038, $style->og_max);
        $this->assertSame(1.006, $style->fg_min);
        $this->assertSame(1.012, $style->fg_max);
        $this->assertSame(3.0, $style->abv_min);
        $this->assertSame(4.2, $style->abv_max);
        $this->assertSame(20.0, $style->ibu_min);
        $this->assertSame(35.0, $style->ibu_max);
        $this->assertSame(5.0, $style->srm_min);
        $this->assertSame(12.0, $style->srm_max);
        $this->assertNull($style->category_description);
        $this->assertNull($style->history);
        $this->assertNull($style->style_comparison);
        $this->assertNull($style->ingredients);
        $this->assertNull($style->examples);
        $this->assertNull($style->tags);

        $empty = Style::where('code', 'BA-002')->firstOrFail();
        $this->assertNull($empty->og_min);
        $this->assertNull($empty->srm_max);
        $this->assertNull($empty->aroma);
    }

    public function test_a_second_run_does_not_add_rows(): void
    {
        $this->seedFixture();
        $this->seedFixture();

        $this->assertSame(2, Style::where('source', 'ba_beer')->count());
    }

    public function test_database_seeder_adds_168_ba_styles_and_keeps_the_bjcp_count(): void
    {
        $this->seed(BjcpStyleSeeder::class);
        $bjcpCount = Style::where('source', 'bjcp_beer')->count();
        $this->assertGreaterThan(0, $bjcpCount);

        Style::query()->delete();
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($bjcpCount, Style::where('source', 'bjcp_beer')->count());
        $this->assertSame(168, Style::where('source', 'ba_beer')->count());
    }
}
