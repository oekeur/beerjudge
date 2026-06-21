<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompetitionStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Open = 'open';
    case Judging = 'judging';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return __('competition.status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Open => 'info',
            self::Judging => 'warning',
            self::Closed => 'success',
        };
    }
}
