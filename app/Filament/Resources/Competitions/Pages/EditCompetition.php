<?php

namespace App\Filament\Resources\Competitions\Pages;

use App\Filament\Resources\Competitions\CompetitionResource;
use App\Services\BottleNumberAssignmentService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        $lifecycle = array_values(array_filter([
            $record->status === 'draft' ? Action::make('moveToOpen')
                ->label('Move to Open')
                ->color('info')
                ->action(fn () => $record->update(['status' => 'open']))
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'open' ? Action::make('moveToJudging')
                ->label('Move to Judging')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Lock Registration')
                ->modalDescription('This will lock registration and assign bottle numbers. Continue?')
                ->action(function () use ($record) {
                    app(BottleNumberAssignmentService::class)->assign($record);
                    $record->update(['status' => 'judging']);
                })
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'judging' ? Action::make('moveToClosed')
                ->label('Move to Closed')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $record->update(['status' => 'closed']))
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'closed' && ! $record->published_at ? Action::make('publishResults')
                ->label('Publish Results')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $record->update(['published_at' => now()])) : null,

            $record->status === 'closed' ? Action::make('viewResults')
                ->label('View Results')
                ->color('gray')
                ->url(fn () => route('results.index', $record))
                ->openUrlInNewTab() : null,
        ]));

        return array_merge([DeleteAction::make()], $lifecycle);
    }
}
