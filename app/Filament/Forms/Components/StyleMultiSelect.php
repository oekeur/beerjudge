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
            fn ($record): string => "{$record->code} {$record->name}"
        );
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getCategorizedOptions(): array
    {
        return Style::orderBy('code')
            ->get()
            ->groupBy('category_name')
            ->map(fn ($styles) => $styles->mapWithKeys(
                fn ($style) => [$style->id => "{$style->code} {$style->name}"]
            )->all())
            ->all();
    }
}
