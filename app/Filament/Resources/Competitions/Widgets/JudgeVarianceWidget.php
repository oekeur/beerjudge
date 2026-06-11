<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Models\Entry;
use App\Models\Scoresheet;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class JudgeVarianceWidget extends BaseWidget
{
    public ?int $competitionId = null;

    protected static ?string $heading = null;

    public function getHeading(): string
    {
        return __('widget.variance.heading');
    }

    public function table(Table $table): Table
    {
        $entriesTable = (new Entry)->getTable();
        $sheetsTable = (new Scoresheet)->getTable();

        return $table
            ->query(
                Entry::query()
                    ->where('competition_id', $this->competitionId)
                    ->withCount(['scoresheets as sheet_count' => fn (Builder $q) => $q->where('status', 'submitted')])
                    ->withAvg(['scoresheets as avg_total' => fn (Builder $q) => $q->where('status', 'submitted')], 'total')
                    ->addSelect("{$entriesTable}.*")
                    ->selectSub(
                        Scoresheet::query()
                            ->selectRaw('MAX(total) - MIN(total)')
                            ->whereColumn('entry_id', "{$entriesTable}.id")
                            ->where('status', 'submitted'),
                        'spread'
                    )
                    ->whereHas('scoresheets', function ($query) {
                        $query->where('status', 'submitted');
                    }, '>=', 2)
            )
            ->columns([
                Tables\Columns\TextColumn::make('bottle_number')->label(__('widget.variance.column.number'))->sortable(),
                Tables\Columns\TextColumn::make('name')->label(__('widget.variance.column.beer')),
                Tables\Columns\TextColumn::make('sheet_count')->label(__('widget.variance.column.sheets')),
                Tables\Columns\TextColumn::make('avg_total')
                    ->label(__('widget.variance.column.avg_score'))
                    ->formatStateUsing(fn (int|float|string|null $state): float => round((float) $state, 1)),
                Tables\Columns\TextColumn::make('spread')
                    ->label(__('widget.variance.column.spread'))
                    ->badge()
                    ->color(fn (int|float|string|null $state): string => (int) $state > 7 ? 'danger' : 'success')
                    ->formatStateUsing(function (int|float|string|null $state): string {
                        $spread = (int) $state;

                        return $spread > 7 ? "⚠ {$spread} pts" : "{$spread} pts";
                    }),
            ]);
    }
}
