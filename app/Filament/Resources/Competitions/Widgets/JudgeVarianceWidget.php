<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Models\Entry;
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
        return $table
            ->query(
                Entry::query()
                    ->where('competition_id', $this->competitionId)
                    ->withCount(['scoresheets as sheet_count' => fn($q) => $q->where('status', 'submitted')])
                    ->withAvg(['scoresheets as avg_total' => fn($q) => $q->where('status', 'submitted')], 'total')
                    ->addSelect([
                        'entries.*',
                        DB::raw('(SELECT MAX(s.total) - MIN(s.total) FROM scoresheets s WHERE s.entry_id = entries.id AND s.status = \'submitted\') as spread'),
                    ])
                    ->having('sheet_count', '>=', 2)
            )
            ->columns([
                Tables\Columns\TextColumn::make('bottle_number')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Beer'),
                Tables\Columns\TextColumn::make('sheet_count')->label('Sheets'),
                Tables\Columns\TextColumn::make('avg_total')
                    ->label('Avg Score')
                    ->formatStateUsing(fn($state) => round($state, 1)),
                Tables\Columns\TextColumn::make('spread')
                    ->label('Spread')
                    ->badge()
                    ->color(fn($state) => $state > 7 ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => $state > 7 ? "⚠ {$state} pts" : "{$state} pts"),
            ]);
    }
}
