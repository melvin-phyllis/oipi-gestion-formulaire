<?php

namespace App\Enums\Mission;

enum MissionResult: string
{
    case Found = 'found';
    case NotFound = 'not_found';
    case NeedsVerification = 'needs_verification';

    public function label(): string
    {
        return match ($this) {
            self::Found => 'Retrouvé',
            self::NotFound => 'Non retrouvé',
            self::NeedsVerification => 'À vérifier',
        };
    }
}
