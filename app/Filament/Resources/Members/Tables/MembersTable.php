<?php

namespace App\Filament\Resources\Members\Tables;

use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                IconColumn::make('is_admin')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                Filter::make('pending_approval')
                    ->label(__('member.filter.pending_approval'))
                    ->query(fn (Builder $query) => $query->where('is_active', false)),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('member.action.approve'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (Member $record) => ! $record->is_active)
                    ->action(fn (Member $record) => $record->update(['is_active' => true])),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkAction::make('approve')
                    ->label(__('member.action.approve_selected'))
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each->update(['is_active' => true]))
                    ->deselectRecordsAfterCompletion(),
            ])
            ->toolbarActions([]);
    }
}
