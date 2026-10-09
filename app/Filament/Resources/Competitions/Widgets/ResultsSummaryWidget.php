<?php

namespace App\Filament\Resources\Competitions\Widgets;

use App\Models\Competition;
use Filament\Widgets\Widget;

class ResultsSummaryWidget extends Widget
{
    public ?int $competitionId = null;

    protected static bool $isLazy = false;

    protected string $view = 'filament.competition.results-summary';

    protected function getViewData(): array
    {
        $competition = Competition::with(['judges'])
            ->find($this->competitionId);

        return ['competition' => $competition];
    }
}
