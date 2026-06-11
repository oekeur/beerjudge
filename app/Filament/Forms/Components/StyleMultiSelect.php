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
     * @return array<string, array<int, string>>
     */
    public function getCategorizedOptions(): array
    {
        $categories = [];

        foreach (Style::orderBy('code')->get() as $style) {
            $categories[$style->category_name][$style->id] = "{$style->code} {$style->name}";
        }

        return $categories;
    }
}
