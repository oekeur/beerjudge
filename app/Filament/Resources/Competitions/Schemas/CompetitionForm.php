<?php

namespace App\Filament\Resources\Competitions\Schemas;

use App\Enums\CompetitionStatus;
use App\Filament\Forms\Components\StyleMultiSelect;
use App\Filament\Resources\Competitions\Pages\EditCompetition;
use App\Filament\Resources\Competitions\Widgets\JudgeVarianceWidget;
use App\Filament\Resources\Competitions\Widgets\ResultsSummaryWidget;
use App\Models\Competition;
use App\Models\Member;
use App\Models\Style;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class CompetitionForm
{
    /**
     * All members, inactive included so existing entries still validate. Memoized per request:
     * the Repeater validates every row on save and would otherwise look the member up per row.
     *
     * @return array<array-key, mixed>
     */
    private static function memberOptions(): array
    {
        return once(fn (): array => Member::orderBy('name')->pluck('name', 'id')->all());
    }

    /**
     * Style options for the entry rows, restricted to the competition's styles when any are
     * selected. Keyed on the style-id array only: capturing an object would defeat the memo.
     *
     * @param  array<array-key, mixed>  $styleIds
     * @return array<array-key, mixed>
     */
    private static function styleOptions(array $styleIds): array
    {
        return once(function () use ($styleIds): array {
            $query = Style::orderBy('code');
            if (! empty($styleIds)) {
                $query->whereIn('id', $styleIds);
            }

            return $query->get()
                ->mapWithKeys(fn (Style $s) => [$s->id => "{$s->code} — {$s->name}"])
                ->toArray();
        });
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make(__('competition.tab.overview'))
                            ->schema([
                                TextInput::make('name')
                                    ->required(),
                                TextInput::make('year')
                                    ->numeric()
                                    ->required(),
                                DatePicker::make('judging_date')
                                    ->nullable(),
                                Select::make('status')
                                    ->options(CompetitionStatus::class)
                                    ->default(CompetitionStatus::Draft)
                                    ->disabled(),
                            ]),

                        Tab::make(__('competition.tab.styles'))
                            ->schema([
                                StyleMultiSelect::make('styles')
                                    ->relationship('styles', 'name')
                                    ->rule(fn (?Competition $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                                        if (! $record?->exists) {
                                            return;
                                        }

                                        $kept = array_map(fn (mixed $id): int => is_numeric($id) ? (int) $id : 0, is_array($value) ? $value : []);
                                        $removed = $record->entries()
                                            ->whereNotIn('style_id', $kept)
                                            ->distinct()
                                            ->pluck('style_id');

                                        if ($removed->isEmpty()) {
                                            return;
                                        }

                                        $names = Style::whereIn('id', $removed)->orderBy('code')->get()
                                            ->map(fn (Style $style): string => "{$style->code} {$style->name}")
                                            ->implode(', ');

                                        $fail(__('competition.styles_in_use', ['styles' => $names]));
                                    })
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.entries'))
                            ->id('entries')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Html::make(fn (?Competition $record) => $record?->exists
                                            ? new HtmlString(self::copyableLinkHtml(route('competitions.entries.create', $record)))
                                            : new HtmlString('<p class="text-sm text-gray-500">'.__('competition.save_first').'</p>')
                                        ),
                                        Actions::make([
                                            Action::make('sortByAbv')
                                                ->label(__('competition.entry.sort_by_abv'))
                                                ->color('gray')
                                                ->action(function (Get $get, Set $set): void {
                                                    $state = $get('entries');
                                                    $entries = is_array($state) ? $state : [];
                                                    // uasort is stable (PHP 8+) and keeps the item keys, so saved rows stay bound.
                                                    $abv = fn (mixed $entry): ?float => is_array($entry) && is_numeric($entry['abv'] ?? null) ? (float) $entry['abv'] : null;
                                                    uasort($entries, fn (mixed $a, mixed $b): int => [$abv($a) === null, $abv($a)] <=> [$abv($b) === null, $abv($b)]);

                                                    $set('entries', $entries);
                                                }),
                                        ])->key('entriesActions'),
                                    ])
                                    ->columnSpanFull()
                                    ->visibleOn('edit'),

                                Repeater::make('entries')
                                    ->relationship()
                                    ->schema([
                                        Select::make('member_id')
                                            ->label(__('competition.entry.member'))
                                            ->options(fn (): array => self::memberOptions())
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(2),
                                        Select::make('style_id')
                                            ->label(__('competition.entry.style'))
                                            ->searchable()
                                            ->required()
                                            ->options(fn (Get $get): array => self::styleOptions(is_array($styles = $get('../../styles')) ? $styles : []))
                                            ->live()
                                            ->columnSpan(2),
                                        TextInput::make('abv')
                                            ->label(__('competition.entry.abv'))
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->nullable()
                                            ->suffix('%')
                                            ->columnSpan(1),
                                        Textarea::make('brewer_notes')
                                            ->label(__('competition.entry.brewer_notes'))
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(5)
                                    ->defaultItems(0)
                                    ->orderColumn('sort_order')
                                    ->saveRelationshipsUsing(fn (Repeater $component) => self::saveEntries($component))
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.dashboard'))
                            ->id('dashboard')
                            ->schema([
                                Select::make('judges')
                                    ->multiple()
                                    ->relationship('judges', 'name')
                                    ->saveRelationshipsUsing(null)
                                    ->visible(fn (?Competition $record): bool => (bool) $record?->exists)
                                    ->live()
                                    ->afterStateUpdated(function (?Competition $record, ?array $state, ?array $old): void {
                                        $toInt = fn (mixed $id): int => is_numeric($id) ? (int) $id : 0;
                                        $new = collect(array_map($toInt, $state ?? []));
                                        $previous = collect(array_map($toInt, $old ?? []));

                                        $record?->judges()->syncWithoutDetaching($new->diff($previous)->values()->all());
                                        $record?->judges()->detach($previous->diff($new)->values()->all());
                                    })
                                    ->options(fn () => Member::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->hint(fn (?Competition $record) => $record?->exists
                                        ? new HtmlString(self::judgeJoinLinkHint(route('judging.show', ['competition' => $record->id, 'register' => 1])))
                                        : null
                                    )
                                    ->columnSpanFull(),

                                Livewire::make(ResultsSummaryWidget::class, fn (?Competition $record): array => ['competitionId' => $record?->id])
                                    ->key(fn (?Competition $record): string => 'results-summary-'.($record->id ?? 'new'))
                                    ->hidden(fn (?Competition $record): bool => ! $record?->exists)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.results'))
                            ->schema([
                                Livewire::make(JudgeVarianceWidget::class, fn (?Competition $record): array => ['competitionId' => $record?->id])
                                    ->key(fn (?Competition $record): string => 'judge-variance-'.($record->id ?? 'new'))
                                    ->hidden(fn (?Competition $record): bool => ! $record?->exists)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    /**
     * Wraps Repeater::saveToRelationship so a Save only deletes entries the page loaded and the
     * admin removed. Entries registered after the page loaded ("late entries") are kept and
     * moved behind the page's rows in sort_order, in id order. The loaded set lives on
     * EditCompetition and is refreshed here from the records that survive the save, never from
     * a fresh query, so rows added on the page count as loaded for the next Save.
     *
     * Relies on saveToRelationship mutating the collection from getCachedExistingRecords and
     * handing that same collection to setRelation; re-check on Filament upgrades.
     */
    private static function saveEntries(Repeater $component): void
    {
        $livewire = $component->getLivewire();

        if (! $livewire instanceof EditCompetition) {
            $component->saveToRelationship();

            return;
        }

        $existing = $component->getCachedExistingRecords();
        $late = $existing
            ->reject(fn (Model $record): bool => in_array(self::recordId($record), $livewire->loadedEntryIds, true))
            ->sortBy(fn (Model $record): mixed => $record->getKey());

        foreach ($late->keys() as $key) {
            $existing->forget($key);
        }

        $component->saveToRelationship();

        $next = $existing->count();

        foreach ($late as $key => $record) {
            $record->forceFill(['sort_order' => ++$next])->saveQuietly();
            $existing->put($key, $record);
        }

        $livewire->loadedEntryIds = $existing->map(fn (Model $record): int => self::recordId($record))->values()->all();
    }

    private static function recordId(Model $record): int
    {
        $key = $record->getKey();

        return is_numeric($key) ? (int) $key : 0;
    }

    private static function copyableLinkHtml(string $url): string
    {
        $escaped = e($url);
        $json = e((string) json_encode($url));
        $labelCopy = e((string) json_encode(__('competition.invite_copy')));
        $labelCopied = e((string) json_encode(__('competition.invite_copied')));

        return <<<HTML
        <div class="flex items-center gap-2 text-sm">
            <span class="font-mono break-all text-gray-700 dark:text-gray-300">{$escaped}</span>
            <button
                x-data="{ copied: false }"
                x-on:click="navigator.clipboard.writeText({$json}); copied = true; setTimeout(() => copied = false, 2000)"
                type="button"
                class="shrink-0 inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 transition-colors"
            >
                <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                <span x-text="copied ? {$labelCopied} : {$labelCopy}"></span>
            </button>
        </div>
        HTML;
    }

    private static function judgeJoinLinkHint(string $url): string
    {
        $json = e((string) json_encode($url));
        $labelCopy = e((string) json_encode(__('competition.invite_copy')));
        $labelCopied = e((string) json_encode(__('competition.invite_copied')));

        return <<<HTML
        <button
            x-data="{ copied: false }"
            x-on:click="navigator.clipboard.writeText({$json}); copied = true; setTimeout(() => copied = false, 2000)"
            type="button"
            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold bg-amber-500/15 text-amber-500 hover:bg-amber-500/25 dark:bg-amber-400/15 dark:text-amber-400 dark:hover:bg-amber-400/25 transition-colors"
        >
            <svg x-show="!copied" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
            <svg x-show="copied" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            <span x-text="copied ? {$labelCopied} : {$labelCopy}"></span>
        </button>
        HTML;
    }
}
