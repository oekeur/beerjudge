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
                ->label(__('competition.action.move_to_open'))
                ->color('info')
                ->action(fn () => $record->update(['status' => 'open']))
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'open' ? Action::make('moveToJudging')
                ->label(__('competition.action.move_to_judging'))
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('competition.action.lock_registration.heading'))
                ->modalDescription(__('competition.action.lock_registration.description'))
                ->action(function () use ($record) {
                    app(BottleNumberAssignmentService::class)->assign($record);
                    $record->update(['status' => 'judging']);
                })
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'judging' ? Action::make('moveToClosed')
                ->label(__('competition.action.move_to_closed'))
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $record->update(['status' => 'closed']))
                ->after(fn () => $this->refreshFormData(['status'])) : null,

            $record->status === 'closed' ? Action::make('viewResults')
                ->label(__('competition.action.view_results'))
                ->color('gray')
                ->url(fn () => route('results.index', $record))
                ->openUrlInNewTab() : null,
        ]));

        return array_merge([DeleteAction::make()], $lifecycle);
    }
}
