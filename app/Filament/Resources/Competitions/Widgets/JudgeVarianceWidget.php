<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Enums\ScoresheetStatus;
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
            ->poll('5s')
            ->query(
                Entry::query()
                    ->with('style')
                    ->where('competition_id', $this->competitionId)
                    ->withCount(['scoresheets as sheet_count' => fn (Builder $q) => $q->where('status', ScoresheetStatus::Submitted)])
                    ->withAvg(['scoresheets as avg_total' => fn (Builder $q) => $q->where('status', ScoresheetStatus::Submitted)], 'total')
                    ->addSelect("{$entriesTable}.*")
                    ->selectSub(
                        Scoresheet::query()
                            ->selectRaw('MAX(total) - MIN(total)')
                            ->whereColumn('entry_id', "{$entriesTable}.id")
                            ->where('status', ScoresheetStatus::Submitted),
                        'spread'
                    )
                    ->whereHas('scoresheets', function ($query) {
                        $query->where('status', ScoresheetStatus::Submitted);
                    }, '>=', 2)
            )
            ->columns([
                Tables\Columns\TextColumn::make('bottle_number')->label(__('widget.variance.column.number'))->sortable(),
                Tables\Columns\TextColumn::make('style.code')
                    ->label(__('widget.variance.column.beer'))
                    ->state(fn (Entry $record): string => trim("{$record->style?->code} {$record->style?->name}")),
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

                        return $spread > 7 ? "⚠ {$spread} ".__('widget.variance.points') : "{$spread} ".__('widget.variance.points');
                    }),
            ]);
    }
}
