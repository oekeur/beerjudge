<?php

namespace App\Filament\Resources\Styles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StyleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->required(),
                TextInput::make('category_name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('version')
                    ->required()
                    ->default('2021'),
                TextInput::make('og_min')
                    ->numeric(),
                TextInput::make('og_max')
                    ->numeric(),
                TextInput::make('fg_min')
                    ->numeric(),
                TextInput::make('fg_max')
                    ->numeric(),
                TextInput::make('abv_min')
                    ->numeric(),
                TextInput::make('abv_max')
                    ->numeric(),
                TextInput::make('ibu_min')
                    ->numeric(),
                TextInput::make('ibu_max')
                    ->numeric(),
                TextInput::make('srm_min')
                    ->numeric(),
                TextInput::make('srm_max')
                    ->numeric(),
            ]);
    }
}
