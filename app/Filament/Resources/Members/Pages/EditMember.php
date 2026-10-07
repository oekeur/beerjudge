<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action, Member $record): void {
                    if ($record->isLastActiveAdmin()) {
                        Notification::make()
                            ->danger()
                            ->title(__('members.last_admin_delete'))
                            ->send();

                        $action->halt();
                    }
                }),
        ];
    }
}
