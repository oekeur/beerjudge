<?php

namespace App\Filament\Resources\Competitions\Pages;

use App\Enums\CompetitionStatus;
use App\Filament\Resources\Competitions\CompetitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCompetition extends CreateRecord
{
    protected static string $resource = CompetitionResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = CompetitionStatus::Draft;

        return $data;
    }
}
