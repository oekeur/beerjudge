<?php

namespace App\Filament\Resources\Styles\Tables;

use App\Models\Style;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StylesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'custom' => 'warning',
                        'bjcp_mead' => 'info',
                        'bjcp_cider' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Style::SOURCE_LABELS[$state] ?? $state),
                TextColumn::make('category_name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(Style::SOURCE_LABELS),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
