<?php

namespace App\Filament\Resources\Styles\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StylesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('category_name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
