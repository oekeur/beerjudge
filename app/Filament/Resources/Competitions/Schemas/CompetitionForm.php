<?php

namespace App\Filament\Resources\Competitions\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CompetitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('year')
                    ->numeric()
                    ->required(),
                DatePicker::make('judging_date')
                    ->nullable(),
                Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'open' => 'Open',
                        'judging' => 'Judging',
                        'closed' => 'Closed',
                    ])
                    ->default('draft'),
                DateTimePicker::make('published_at')
                    ->nullable(),
                CheckboxList::make('styles')
                    ->relationship('styles', 'name')
                    ->searchable()
                    ->columns(3),
            ]);
    }
}
