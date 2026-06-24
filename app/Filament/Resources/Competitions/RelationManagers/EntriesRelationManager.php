<?php

namespace App\Filament\Resources\Competitions\RelationManagers;

use App\Models\Style;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'entries';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('member_id')
                    ->relationship('member', 'name')
                    ->searchable()
                    ->required(),
                Select::make('style_id')
                    ->relationship(
                        name: 'style',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->whereHas(
                            'competitions',
                            fn (Builder $q) => $q->where('competitions.id', $this->getOwnerRecord()->getKey())
                        )
                    )
                    ->searchable()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn (Style $record): string => "{$record->code} — {$record->name}"),
                TextInput::make('bottle_number')
                    ->numeric()
                    ->nullable(),
                Textarea::make('brewer_notes')
                    ->nullable(),
                TextInput::make('abv')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0)
                    ->maxValue(100)
                    ->nullable()
                    ->suffix('%'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('member.name')
                    ->label('Member'),
                TextColumn::make('style.code')
                    ->label('Style'),
                TextColumn::make('bottle_number')
                    ->label('#'),
                TextColumn::make('abv')
                    ->label('ABV')
                    ->suffix('%')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->reorderable('bottle_number')
            ->defaultSort('bottle_number')
            ->filters([])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }
}
