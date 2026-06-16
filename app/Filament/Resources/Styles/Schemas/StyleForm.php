<?php

namespace App\Filament\Resources\Styles\Schemas;

use App\Models\Style;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StyleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('style.section.identity'))
                    ->columns(2)
                    ->schema([
                        Select::make('source')
                            ->options(Style::SOURCE_LABELS)
                            ->default('custom')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('code')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('version')
                            ->nullable()
                            ->default(null)
                            ->columnSpan(1),
                        TextInput::make('category_name')
                            ->required()
                            ->columnSpan(1),
                        TextInput::make('name')
                            ->required()
                            ->columnSpan(1),
                    ]),

                Section::make(__('style.section.vital_statistics'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('og_min')->numeric()->columnSpan(1),
                        TextInput::make('og_max')->numeric()->columnSpan(1),
                        TextInput::make('fg_min')->numeric()->columnSpan(1),
                        TextInput::make('fg_max')->numeric()->columnSpan(1),
                        TextInput::make('abv_min')->numeric()->columnSpan(1),
                        TextInput::make('abv_max')->numeric()->columnSpan(1),
                        TextInput::make('ibu_min')->numeric()->columnSpan(1),
                        TextInput::make('ibu_max')->numeric()->columnSpan(1),
                        TextInput::make('srm_min')->numeric()->columnSpan(1),
                        TextInput::make('srm_max')->numeric()->columnSpan(1),
                    ]),

                Section::make(__('style.section.judging_descriptors'))
                    ->schema([
                        Textarea::make('overall_impression')->rows(4),
                        Textarea::make('aroma')->rows(5),
                        Textarea::make('appearance')->rows(4),
                        Textarea::make('flavor')->rows(5),
                        Textarea::make('mouthfeel')->rows(3),
                    ]),

                Section::make(__('style.section.additional_information'))
                    ->schema([
                        Textarea::make('comments')->rows(3),
                        Textarea::make('history')->rows(3),
                        Textarea::make('style_comparison')->rows(3),
                        Textarea::make('ingredients')->rows(3),
                        Textarea::make('examples')->rows(2),
                        TextInput::make('tags'),
                    ]),
            ]);
    }
}
