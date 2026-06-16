<?php

namespace App\Filament\Resources\Members\Pages;

use App\Filament\Resources\Members\MemberResource;
use App\Filament\Resources\Members\Widgets\InviteLinksWidget;
use App\Models\Invitation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Livewire\Component as LivewireComponent;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderWidgets(): array
    {
        return [InviteLinksWidget::class];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_invite')
                ->label(__('competition.invite_link_label'))
                ->icon('heroicon-o-link')
                ->schema([
                    Radio::make('type')
                        ->label(__('competition.invite_type_single'))
                        ->options([
                            Invitation::TYPE_SINGLE_USE => __('competition.invite_type_single'),
                            Invitation::TYPE_TIME_BOUND => __('competition.invite_type_time_bound'),
                        ])
                        ->default(Invitation::TYPE_SINGLE_USE)
                        ->required()
                        ->live(),
                    DateTimePicker::make('expires_at')
                        ->label(__('competition.invite_expires_at'))
                        ->nullable()
                        ->visible(fn (Get $get) => $get('type') === Invitation::TYPE_TIME_BOUND)
                        ->requiredIf('type', Invitation::TYPE_TIME_BOUND),
                ])
                ->action(function (array $data, LivewireComponent $livewire): void {
                    $type = is_string($data['type']) ? $data['type'] : Invitation::TYPE_SINGLE_USE;
                    $expiresAt = is_string($data['expires_at'] ?? null) ? Carbon::parse($data['expires_at']) : null;

                    $invitation = Invitation::generate(
                        $type,
                        $expiresAt,
                        (int) auth()->id(),
                    );

                    $url = route('register', ['token' => $invitation->token]);

                    $livewire->js('navigator.clipboard.writeText('.json_encode($url).')');

                    Notification::make()
                        ->title(__('competition.invite_created'))
                        ->body($url)
                        ->persistent()
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }
}
