<?php

namespace App\Enums\Mission;

enum FoundReportDecision: string
{
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Confirmed => 'Confirmé',
            self::Rejected => 'Refusé',
        };
    }
}
