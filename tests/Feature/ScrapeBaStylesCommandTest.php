<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapeBaStylesCommandTest extends TestCase
{
    private const URL = 'https://www.brewersassociation.org/edu/brewers-association-beer-style-guidelines/';

    private string $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = sys_get_temp_dir().'/ba-scrape-'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->output)) {
            unlink($this->output);
        }

        parent::tearDown();
    }

    private function fixture(): string
    {
        return (string) file_get_contents(base_path('tests/Fixtures/ba-style-guidelines.html'));
    }

    public function test_it_writes_the_parsed_styles_as_json(): void
    {
        Http::fake([self::URL => Http::response($this->fixture())]);

        $this->artisan('styles:scrape-ba', ['--output' => $this->output])
            ->expectsOutputToContain('BA-002 Wood- and Barrel-Aged Beer: no og, fg, abv, ibu, srm')
            ->assertSuccessful();

        Http::assertSent(fn ($request) => $request->url() === self::URL
            && str_contains($request->header('User-Agent')[0], 'Mozilla/5.0'));

        $data = json_decode((string) file_get_contents($this->output), true);

        $this->assertSame('2026', $data['edition']);
        $this->assertSame(self::URL, $data['source_url']);
        $this->assertSame(now()->toDateString(), $data['scraped_at']);
        $this->assertCount(5, $data['styles']);

        $common = [
            'aroma' => "Malt: Low to medium residual malt sweetness\nHops: Very low to medium-low",
            'flavor' => "Malt: Low to medium residual malt sweetness\nHops: Very low to medium-low\nBitterness: Medium",
            'appearance' => "Color: Gold to copper-colored\nClarity: Chill haze is allowable",
            'mouthfeel' => 'Body: Low to medium',
        ];
        $fermentation = 'Fermentation: Fruity esters are acceptable. It’s mild.';
        $notes = "\nNotes: Subcategories may be created.";

        $this->assertSame($this->sorted($common + [
            'code' => 'BA-001', 'name' => 'Ordinary Bitter', 'category' => 'British Origin Ale Styles',
            'overall_impression' => $fermentation.$notes,
            'og_min' => 1.033, 'og_max' => 1.038, 'fg_min' => 1.006, 'fg_max' => 1.012,
            'abv_min' => 3.0, 'abv_max' => 4.2, 'ibu_min' => 20, 'ibu_max' => 35,
            'srm_min' => 5, 'srm_max' => 12,
        ]), $this->sorted($data['styles'][0]));

        $this->assertSame($this->sorted($common + [
            'code' => 'BA-002', 'name' => 'Wood- and Barrel-Aged Beer', 'category' => 'British Origin Ale Styles',
            'overall_impression' => $fermentation.$notes,
            'og_min' => null, 'og_max' => null, 'fg_min' => null, 'fg_max' => null,
            'abv_min' => null, 'abv_max' => null, 'ibu_min' => null, 'ibu_max' => null,
            'srm_min' => null, 'srm_max' => null,
        ]), $this->sorted($data['styles'][1]));

        $this->assertSame($this->sorted($common + [
            'code' => 'BA-003', 'name' => 'Imperial Stout', 'category' => 'German Origin Ale Styles',
            'overall_impression' => $fermentation,
            'og_min' => 1.075, 'og_max' => 1.115, 'fg_min' => 1.018, 'fg_max' => 1.03,
            'abv_min' => 8, 'abv_max' => null, 'ibu_min' => 50, 'ibu_max' => 80,
            'srm_min' => 40, 'srm_max' => null,
        ]), $this->sorted($data['styles'][2]));

        $this->assertSame($this->sorted($common + [
            'code' => 'BA-004', 'name' => 'Non-Alcoholic Beer', 'category' => 'German Origin Ale Styles',
            'overall_impression' => $fermentation.$notes,
            'og_min' => 1.006, 'og_max' => 1.02, 'fg_min' => 1.0, 'fg_max' => 1.008,
            'abv_min' => null, 'abv_max' => 0.5, 'ibu_min' => 5, 'ibu_max' => 30,
            'srm_min' => 2, 'srm_max' => 10,
        ]), $this->sorted($data['styles'][3]));

        $this->assertSame($this->sorted($common + [
            'code' => 'BA-005', 'name' => 'Fruit Beer', 'category' => 'German Origin Ale Styles',
            'overall_impression' => $fermentation.$notes,
            'og_min' => 1.04, 'og_max' => 1.072, 'fg_min' => 1.008, 'fg_max' => 1.016,
            'abv_min' => 4.0, 'abv_max' => 9.0, 'ibu_min' => 5, 'ibu_max' => 30,
            'srm_min' => 5, 'srm_max' => 50,
        ]), $this->sorted($data['styles'][4]));
    }

    public function test_it_fails_without_writing_when_the_page_has_no_styles(): void
    {
        Http::fake([self::URL => Http::response('<html><body><h2 class="origin">Empty</h2></body></html>')]);

        $this->artisan('styles:scrape-ba', ['--output' => $this->output])
            ->expectsOutputToContain('No styles found')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->output);
    }

    public function test_it_fails_without_writing_when_a_style_lacks_a_required_label(): void
    {
        $html = str_replace('<li><strong>Body:</strong> Low to medium</li>', '', $this->fixture());
        Http::fake([self::URL => Http::response($html)]);

        $this->artisan('styles:scrape-ba', ['--output' => $this->output])
            ->expectsOutputToContain('lacks the label "Body"')
            ->assertFailed();

        $this->assertFileDoesNotExist($this->output);
    }

    public function test_it_fails_without_writing_when_the_request_fails(): void
    {
        Http::fake([self::URL => Http::response('nope', 503)]);

        $this->artisan('styles:scrape-ba', ['--output' => $this->output])
            ->assertFailed();

        $this->assertFileDoesNotExist($this->output);
    }

    /**
     * @param  array<string, mixed>  $style
     * @return array<string, mixed>
     */
    private function sorted(array $style): array
    {
        ksort($style);

        return $style;
    }
}
