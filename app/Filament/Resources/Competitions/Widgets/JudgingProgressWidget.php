<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Models\Competition;
use Filament\Widgets\Widget;

class JudgingProgressWidget extends Widget
{
    public ?int $competitionId = null;

    protected ?string $pollingInterval = '5s';

    protected static bool $isLazy = false;

    protected string $view = 'filament.competition.judging-progress';

    protected function getViewData(): array
    {
        $competition = Competition::with([
            'judges',
            'entries.member',
            'entries.style',
            'entries.scoresheets',
        ])->find($this->competitionId);

        return ['competition' => $competition];
    }
}
