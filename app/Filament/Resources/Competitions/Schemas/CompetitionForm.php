<?php

namespace App\Filament\Resources\Competitions\Schemas;

use App\Enums\CompetitionStatus;
use App\Filament\Forms\Components\StyleMultiSelect;
use App\Filament\Resources\Competitions\CompetitionResource;
use App\Filament\Resources\Competitions\Widgets\JudgeVarianceWidget;
use App\Filament\Resources\Competitions\Widgets\ResultsSummaryWidget;
use App\Models\Competition;
use App\Models\Entry;
use App\Models\Member;
use App\Models\Style;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CompetitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->columnSpanFull()
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
                                    ->default(CompetitionStatus::Draft),
                            ]),

                        Tab::make(__('competition.tab.styles'))
                            ->schema([
                                StyleMultiSelect::make('styles')
                                    ->relationship('styles', 'name')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.entries'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Html::make(fn (?Competition $record) => $record?->exists
                                            ? new HtmlString(self::copyableLinkHtml(route('competitions.entries.create', $record)))
                                            : new HtmlString('<p class="text-sm text-gray-500">'.__('competition.save_first').'</p>')
                                        ),
                                        Actions::make([
                                            Action::make('sortByAbv')
                                                ->label('Sort by ABV ↑')
                                                ->color('gray')
                                                ->action(function (EditRecord $livewire): void {
                                                    /** @var Competition $competition */
                                                    $competition = $livewire->getRecord();
                                                    $ids = $competition->entries()
                                                        ->orderByRaw('abv IS NULL')
                                                        ->orderBy('abv')
                                                        ->pluck('id');

                                                    foreach ($ids as $i => $id) {
                                                        Entry::whereKey($id)->update(['sort_order' => (int) $i + 1]);
                                                    }

                                                    $livewire->redirect(
                                                        CompetitionResource::getUrl('edit', ['record' => $competition])
                                                    );
                                                }),
                                        ]),
                                    ])
                                    ->columnSpanFull()
                                    ->visibleOn('edit'),

                                Repeater::make('entries')
                                    ->relationship()
                                    ->schema([
                                        Select::make('member_id')
                                            ->relationship('member', 'name')
                                            ->searchable()
                                            ->required()
                                            ->columnSpan(2),
                                        Select::make('style_id')
                                            ->searchable()
                                            ->required()
                                            ->options(function (Get $get): array {
                                                $styleIds = $get('../../styles') ?? [];
                                                $query = Style::orderBy('code');
                                                if (! empty($styleIds)) {
                                                    $query->whereIn('id', $styleIds);
                                                }

                                                return $query->get()
                                                    ->mapWithKeys(fn (Style $s) => [$s->id => "{$s->code} — {$s->name}"])
                                                    ->toArray();
                                            })
                                            ->live()
                                            ->columnSpan(2),
                                        TextInput::make('abv')
                                            ->numeric()
                                            ->step(0.1)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->nullable()
                                            ->suffix('%')
                                            ->columnSpan(1),
                                        Textarea::make('brewer_notes')
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(5)
                                    ->defaultItems(0)
                                    ->orderColumn('sort_order')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.dashboard'))
                            ->schema([
                                Select::make('judges')
                                    ->multiple()
                                    ->relationship('judges', 'name')
                                    ->options(fn () => Member::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->columnSpanFull(),

                                Html::make(fn (?Competition $record) => $record?->exists
                                    ? new HtmlString(self::copyableLinkHtml(route('judging.show', ['competition' => $record->id, 'register' => 1])))
                                    : new HtmlString('<p class="text-sm text-gray-500">'.__('competition.save_first').'</p>')
                                )
                                    ->columnSpanFull()
                                    ->visibleOn('edit'),

                                Livewire::make(ResultsSummaryWidget::class, fn (?Competition $record): array => ['competitionId' => $record?->id])
                                    ->key(fn (?Competition $record): string => 'results-summary-'.($record?->id ?? 'new'))
                                    ->hidden(fn (?Competition $record): bool => ! $record?->exists)
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.results'))
                            ->schema([
                                Livewire::make(JudgeVarianceWidget::class, fn (?Competition $record): array => ['competitionId' => $record?->id])
                                    ->key(fn (?Competition $record): string => 'judge-variance-'.($record?->id ?? 'new'))
                                    ->hidden(fn (?Competition $record): bool => ! $record?->exists)
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
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
}
