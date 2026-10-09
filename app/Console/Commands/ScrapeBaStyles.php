<?php

namespace App\Console\Commands;

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use JsonException;

#[Signature('styles:scrape-ba {--output= : Path of the JSON file to write (default: ba_styleguide-2026.json in the project root)}')]
#[Description('Scrape the Brewers Association beer style guidelines into a JSON snapshot')]
class ScrapeBaStyles extends Command
{
    private const EDITION = '2026';

    private const SOURCE_URL = 'https://www.brewersassociation.org/edu/brewers-association-beer-style-guidelines/';

    private const USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64; rv:130.0) Gecko/20100101 Firefox/130.0';

    private const REQUIRED_LABELS = [
        'Color',
        'Clarity',
        'Perceived Malt Aroma & Flavor',
        'Perceived Hop Aroma & Flavor',
        'Perceived Bitterness',
        'Fermentation Characteristics',
        'Body',
    ];

    public function handle(): int
    {
        $outputOption = $this->option('output');
        $output = is_string($outputOption) && $outputOption !== ''
            ? $outputOption
            : base_path('ba_styleguide-2026.json');

        $response = Http::withUserAgent(self::USER_AGENT)->timeout(60)->get(self::SOURCE_URL);

        if ($response->failed()) {
            $this->error("Fetching the BA page failed with HTTP status {$response->status()}.");

            return self::FAILURE;
        }

        $document = HTMLDocument::createFromString($response->body(), LIBXML_NOERROR);
        $styles = [];
        $category = '';

        foreach ($document->querySelectorAll('h2.origin, div.beer-style') as $node) {
            if ($node->tagName === 'H2') {
                $category = $this->clean($node->textContent ?? '');

                continue;
            }

            $code = sprintf('BA-%03d', count($styles) + 1);
            $style = $this->parseStyle($node, $code, $category);

            if (is_string($style)) {
                $this->error($style);

                return self::FAILURE;
            }

            $styles[] = $style;
        }

        if ($styles === []) {
            $this->error('No styles found on the BA page; the markup may have changed.');

            return self::FAILURE;
        }

        try {
            $json = json_encode([
                'edition' => self::EDITION,
                'source_url' => self::SOURCE_URL,
                'scraped_at' => now()->toDateString(),
                'styles' => $styles,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->error("Encoding the styles failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        file_put_contents($output, $json."\n");

        foreach ($styles as $style) {
            $empty = array_filter(
                ['og', 'fg', 'abv', 'ibu', 'srm'],
                fn (string $stat): bool => $style["{$stat}_min"] === null && $style["{$stat}_max"] === null,
            );

            if ($empty !== []) {
                $this->line("{$style['code']} {$style['name']}: no ".implode(', ', $empty));
            }
        }

        $this->info(count($styles)." styles written to {$output}");

        return self::SUCCESS;
    }

    /**
     * @return array{code: string, name: string, category: string, appearance: string, aroma: string, flavor: string, mouthfeel: string, overall_impression: string, og_min: int|float|null, og_max: int|float|null, fg_min: int|float|null, fg_max: int|float|null, abv_min: int|float|null, abv_max: int|float|null, ibu_min: int|float|null, ibu_max: int|float|null, srm_min: int|float|null, srm_max: int|float|null}|string the style, or an error message
     */
    private function parseStyle(Element $node, string $code, string $category): array|string
    {
        $items = $node->querySelectorAll('ul.list-with-heading > li');
        if ($items->length === 0) {
            return "Style {$code} has no list items; the markup may have changed.";
        }

        $name = $this->clean($items->item(0)->textContent ?? '');

        $fields = [];
        foreach ($items as $item) {
            $pair = $this->labelAndValue($item);
            if ($pair !== null) {
                $fields[rtrim($pair[0], ':')] = $pair[1];
            }
        }

        foreach (self::REQUIRED_LABELS as $required) {
            if (! array_key_exists($required, $fields)) {
                return "Style {$code} ({$name}) lacks the label \"{$required}\"; the markup may have changed.";
            }
        }

        $stats = [];
        foreach ($node->querySelectorAll('ul.horizontal > li') as $item) {
            $pair = $this->labelAndValue($item);
            if ($pair !== null) {
                $stats[$pair[0]] = $pair[1];
            }
        }

        $malt = "Malt: {$fields['Perceived Malt Aroma & Flavor']}\nHops: {$fields['Perceived Hop Aroma & Flavor']}";
        $overall = "Fermentation: {$fields['Fermentation Characteristics']}";
        if (($fields['Additional notes'] ?? '') !== '') {
            $overall .= "\nNotes: {$fields['Additional notes']}";
        }

        [$ogMin, $ogMax] = $this->parseRange($this->statValue($stats, 'Original Gravity'));
        [$fgMin, $fgMax] = $this->parseRange($this->statValue($stats, 'Apparent Extract'));
        [$abvMin, $abvMax] = $this->parseRange($this->bracketed($this->statValue($stats, 'Alcohol by')));
        [$ibuMin, $ibuMax] = $this->parseRange($this->statValue($stats, 'Bitterness (IBU)'));
        [$srmMin, $srmMax] = $this->parseRange($this->statValue($stats, 'Color SRM'));

        return [
            'code' => $code,
            'name' => $name,
            'category' => $category,
            'appearance' => "Color: {$fields['Color']}\nClarity: {$fields['Clarity']}",
            'aroma' => $malt,
            'flavor' => "{$malt}\nBitterness: {$fields['Perceived Bitterness']}",
            'mouthfeel' => "Body: {$fields['Body']}",
            'overall_impression' => $overall,
            'og_min' => $ogMin, 'og_max' => $ogMax,
            'fg_min' => $fgMin, 'fg_max' => $fgMax,
            'abv_min' => $abvMin, 'abv_max' => $abvMax,
            'ibu_min' => $ibuMin, 'ibu_max' => $ibuMax,
            'srm_min' => $srmMin, 'srm_max' => $srmMax,
        ];
    }

    /**
     * Splits `<li><strong>Label</strong> value</li>` into cleaned label and value text.
     *
     * @return array{0: string, 1: string}|null
     */
    private function labelAndValue(Element $item): ?array
    {
        $label = $item->querySelector('strong');
        if ($label === null) {
            return null;
        }

        $full = $item->textContent ?? '';
        $labelText = $label->textContent ?? '';
        $position = (int) strpos($full, $labelText);

        return [
            $this->clean($labelText),
            $this->clean(substr_replace($full, '', $position, strlen($labelText))),
        ];
    }

    private function clean(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param  array<string, string>  $stats
     */
    private function statValue(array $stats, string $labelPrefix): string
    {
        foreach ($stats as $label => $value) {
            if (str_starts_with($label, $labelPrefix)) {
                return $value;
            }
        }

        return '';
    }

    /** Returns the text inside the first pair of brackets, or '' when there is none. */
    private function bracketed(string $value): string
    {
        return preg_match('/\(([^)]*)\)/', $value, $m) === 1 ? $m[1] : '';
    }

    /**
     * Parses the leading range token of a stat value; anything unrecognised gives [null, null].
     *
     * @return array{0: int|float|null, 1: int|float|null}
     */
    private function parseRange(string $value): array
    {
        $number = '(\d+(?:\.\d+)?)';
        $value = trim($value);

        if (preg_match("/^<\s*{$number}/", $value, $m) === 1) {
            return [null, $this->number($m[1])];
        }

        if (preg_match("/^{$number}\s*%?\s*-\s*{$number}\s*%?\s*(\+)?/", $value, $m) === 1) {
            return [$this->number($m[1]), isset($m[3]) ? null : $this->number($m[2])];
        }

        if (preg_match("/^{$number}\s*%?\s*\+/", $value, $m) === 1) {
            return [$this->number($m[1]), null];
        }

        return [null, null];
    }

    private function number(string $text): int|float
    {
        return str_contains($text, '.') ? (float) $text : (int) $text;
    }
}
