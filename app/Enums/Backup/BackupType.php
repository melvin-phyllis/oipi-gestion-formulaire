<?php

namespace App\Enums\Backup;

enum BackupType: string
{
    case Manual = 'manual';
    case Scheduled = 'scheduled';
    case PreRestore = 'pre_restore';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manuelle',
            self::Scheduled => 'Automatique',
            self::PreRestore => 'Sécurité avant restauration',
        };
    }
}
