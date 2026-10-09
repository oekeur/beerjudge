<?php

namespace App\Filament\Forms\Components;

use App\Models\Style;
use Filament\Forms\Components\CheckboxList;

class StyleMultiSelect extends CheckboxList
{
    protected string $view = 'filament.forms.components.style-multi-select';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getOptionLabelFromRecordUsing(
            fn (Style $record): string => "{$record->code} {$record->name}"
        );
    }

    /**
     * @return array<string, array{label: string, categories: array<string, array<int, string>>}>
     */
    public function getCategorizedOptions(): array
    {
        $sources = [];

        $sourceOrder = array_flip(array_keys(Style::SOURCE_LABELS));
        $styles = Style::orderBy('code')->get()
            ->sortBy(fn (Style $style): int => $sourceOrder[$style->source] ?? count($sourceOrder))
            ->values();

        foreach ($styles as $style) {
            $sourceKey = $style->source;
            $sourceLabel = Style::SOURCE_LABELS[$sourceKey] ?? $sourceKey;
            $fullLabel = $style->version ? $sourceLabel.' '.$style->version : $sourceLabel;

            if (! isset($sources[$sourceKey])) {
                $sources[$sourceKey] = ['label' => $fullLabel, 'categories' => []];
            }

            $sources[$sourceKey]['categories'][$style->category_name][$style->id]
                = "{$style->code} {$style->name}";
        }

        return $sources;
    }
}
