<?php

namespace App\Enums\Mission;

enum MissionReminderType: string
{
    case BeforeDue = 'before_due';
    case DueToday = 'due_today';
    case Overdue = 'overdue';
    case Escalation = 'escalation';

    public function label(): string
    {
        return match ($this) {
            self::BeforeDue => 'Échéance proche',
            self::DueToday => 'Échéance aujourd’hui',
            self::Overdue => 'Mission en retard',
            self::Escalation => 'Escalade de mission',
        };
    }
}
