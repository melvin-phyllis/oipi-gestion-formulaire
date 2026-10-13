<?php

namespace App\Enums\Mission;

enum MissionStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isActive(): bool
    {
        return match ($this) {
            self::Assigned, self::InProgress => true,
            default => false,
        };
    }
}
