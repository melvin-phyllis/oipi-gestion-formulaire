<?php

namespace App\Enums\Mission;

enum MissionAssignmentNotificationType: string
{
    case Assigned = 'assigned';
    case Reassigned = 'reassigned';
    case Withdrawn = 'withdrawn';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Nouvelle mission affectée',
            self::Reassigned => 'Mission réaffectée',
            self::Withdrawn => 'Mission retirée',
            self::Cancelled => 'Mission annulée',
        };
    }
}
