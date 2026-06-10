<?php

namespace App\Filament\Resources\Competitions\RelationManagers;

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
                    ->relationship('style', 'name')
                    ->searchable()
                    ->required()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->code} — {$record->name}"),
                TextInput::make('bottle_number')
                    ->numeric()
                    ->nullable(),
                Textarea::make('brewer_notes')
                    ->nullable(),
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
            ])
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
