<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Models\Entry;
use App\Models\Scoresheet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class JudgeVarianceWidget extends BaseWidget
{
    public ?int $competitionId = null;

    protected static ?string $heading = 'Judge Score Variance';

    public function table(Table $table): Table
    {
        $entriesTable = (new Entry)->getTable();
        $sheetsTable = (new Scoresheet)->getTable();

        return $table
            ->query(
                Entry::query()
                    ->where('competition_id', $this->competitionId)
                    ->withCount(['scoresheets as sheet_count' => fn ($q) => $q->where('status', 'submitted')])
                    ->withAvg(['scoresheets as avg_total' => fn ($q) => $q->where('status', 'submitted')], 'total')
                    ->addSelect([
                        "{$entriesTable}.*",
                        DB::raw("(SELECT MAX(s.total) - MIN(s.total) FROM {$sheetsTable} s WHERE s.entry_id = {$entriesTable}.id AND s.status = 'submitted') as spread"),
                    ])
                    ->whereRaw("(select count(*) from {$sheetsTable} where {$entriesTable}.id = {$sheetsTable}.entry_id and status = ?) >= 2", ['submitted'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('bottle_number')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Beer'),
                Tables\Columns\TextColumn::make('sheet_count')->label('Sheets'),
                Tables\Columns\TextColumn::make('avg_total')
                    ->label('Avg Score')
                    ->formatStateUsing(fn ($state) => round($state, 1)),
                Tables\Columns\TextColumn::make('spread')
                    ->label('Spread')
                    ->badge()
                    ->color(fn ($state) => $state > 7 ? 'danger' : 'success')
                    ->formatStateUsing(fn ($state) => $state > 7 ? "⚠ {$state} pts" : "{$state} pts"),
            ]);
    }
}
