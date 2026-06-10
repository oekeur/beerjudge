<?php

namespace App\Filament\Forms\Components;

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
}
