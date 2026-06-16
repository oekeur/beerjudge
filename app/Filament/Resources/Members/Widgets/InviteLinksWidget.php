<?php

namespace App\Filament\Resources\Members\Widgets;

use App\Models\Invitation;
use Filament\Widgets\Widget;

class InviteLinksWidget extends Widget
{
    protected string $view = 'filament.members.invite-links-widget';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $invitations = Invitation::valid()
            ->where('type', Invitation::TYPE_TIME_BOUND)
            ->orderBy('expires_at')
            ->get();

        return [
            'invitations' => $invitations->map(fn (Invitation $inv) => [
                'url' => route('register', ['token' => $inv->token]),
                'expires_at' => $inv->expires_at?->format('d M Y, H:i'),
            ])->all(),
        ];
    }
}
