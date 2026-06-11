<?php

namespace App\Filament\Resources\Competitions\Schemas;

use App\Filament\Forms\Components\StyleMultiSelect;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
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
                                    ->options([
                                        'draft' => __('competition.status.draft'),
                                        'open' => __('competition.status.open'),
                                        'judging' => __('competition.status.judging'),
                                        'closed' => __('competition.status.closed'),
                                    ])
                                    ->default('draft'),
                            ]),

                        Tab::make(__('competition.tab.styles'))
                            ->schema([
                                StyleMultiSelect::make('styles')
                                    ->relationship('styles', 'name')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.entries'))
                            ->schema([
                                Repeater::make('entries')
                                    ->relationship()
                                    ->schema([
                                        Select::make('member_id')
                                            ->relationship('member', 'name')
                                            ->searchable()
                                            ->required(),
                                        Select::make('style_id')
                                            ->relationship('style', 'name')
                                            ->searchable()
                                            ->required()
                                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} — {$record->name}"),
                                        TextInput::make('bottle_number')
                                            ->numeric()
                                            ->nullable(),
                                        Textarea::make('brewer_notes')
                                            ->nullable()
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->defaultItems(0)
                                    ->reorderable('sort_order')
                                    ->columnSpanFull(),
                            ]),

                        Tab::make(__('competition.tab.results'))
                            ->schema([
                                Placeholder::make('variance_table')
                                    ->label('')
                                    ->content(fn ($record) => $record
                                        ? new HtmlString(view('filament.forms.components.variance-tab', [
                                            'record' => $record,
                                        ])->render())
                                        : new HtmlString('<p class="text-sm text-gray-500">'.__('competition.save_first').'</p>')
                                    )
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
