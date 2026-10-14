<?php

namespace App\Filament\Resources\Competitions\Pages;

use App\Enums\CompetitionStatus;
use App\Filament\Resources\Competitions\CompetitionResource;
use App\Models\Competition;
use App\Services\BottleNumberAssignmentService;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * @property-read Competition $record
 */
class EditCompetition extends EditRecord
{
    protected static string $resource = CompetitionResource::class;

    /**
     * Ids of the entries this page loaded or has saved. Save deletes only these when a row is
     * missing from the form, so entries registered while the page was open survive.
     *
     * @var array<int, int>
     */
    public array $loadedEntryIds = [];

    protected function afterFill(): void
    {
        $entries = $this->data['entries'] ?? [];

        $this->loadedEntryIds = collect(is_array($entries) ? array_keys($entries) : [])
            ->map(fn (int|string $key): int => (int) str_replace('record-', '', (string) $key))
            ->values()
            ->all();
    }

    /**
     * Runs a status change, then rebuilds the header actions in place so the page offers the next
     * status's buttons. Unsaved form edits are left alone; only the status field is refreshed.
     * Actions already in the Livewire action cache stay there (the running action still needs to
     * resolve); only the rendered header list is replaced.
     *
     * @param  Closure(): mixed  $transition
     */
    private function transitionAndRefreshHeader(Closure $transition): void
    {
        $transition();

        $this->refreshFormData(['status']);

        $this->cachedHeaderActions = [];
        $this->cacheInteractsWithHeaderActions();
    }

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        $lifecycle = array_values(array_filter([
            $record->isDraft() ? Action::make('moveToOpen')
                ->label(__('competition.action.move_to_open'))
                ->color('info')
                ->action(fn () => $this->transitionAndRefreshHeader(
                    fn () => $record->transitionTo(CompetitionStatus::Open),
                )) : null,

            $record->isOpen() ? Action::make('moveToJudging')
                ->label(__('competition.action.move_to_judging'))
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('competition.action.lock_registration.heading'))
                ->modalDescription(__('competition.action.lock_registration.description'))
                ->action(fn () => $this->transitionAndRefreshHeader(function () use ($record) {
                    app(BottleNumberAssignmentService::class)->assign($record);
                    $record->transitionTo(CompetitionStatus::Judging);
                })) : null,

            $record->isJudging() ? Action::make('moveToClosed')
                ->label(__('competition.action.move_to_closed'))
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->transitionAndRefreshHeader(
                    fn () => $record->transitionTo(CompetitionStatus::Closed),
                )) : null,

            $record->isClosed() ? Action::make('reopenJudging')
                ->label(__('competition.action.reopen_judging'))
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading(__('competition.action.reopen_judging'))
                ->modalDescription(__('competition.action.reopen_judging_description'))
                ->action(function () use ($record) {
                    $record->transitionTo(CompetitionStatus::Judging);

                    // Header actions are resolved from the status at mount; a redirect rebuilds them.
                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $record]));
                }) : null,

            $record->isJudging() ? Action::make('openJudgingScreen')
                ->label(__('competition.action.open_judging_screen'))
                ->color('gray')
                ->url(fn () => route('judging.show', $record))
                ->openUrlInNewTab() : null,

            $record->isClosed() ? Action::make('viewResults')
                ->label(__('competition.action.view_results'))
                ->color('gray')
                ->url(fn () => route('results.index', $record))
                ->openUrlInNewTab() : null,

            $record->isClosed() ? Action::make('resendResults')
                ->label(__('competition.action.resend_results'))
                ->color('gray')
                ->schema([
                    Select::make('member_id')
                        ->label(__('competition.action.resend_results_member'))
                        ->options(fn (): array => $record->activeParticipants()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data) use ($record): void {
                    $record->sendResultsEmails($record->activeParticipants()->whereKey($data['member_id'])->get());

                    Notification::make()
                        ->title(__('competition.action.resend_results_sent'))
                        ->success()
                        ->send();
                }) : null,
        ]));

        return array_merge([DeleteAction::make()], $lifecycle);
    }
}
