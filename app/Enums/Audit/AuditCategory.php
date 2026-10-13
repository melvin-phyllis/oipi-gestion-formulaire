<?php

namespace App\Enums\Audit;

enum AuditCategory: string
{
    case Authentication = 'authentication';
    case Administration = 'administration';
    case Organization = 'organization';
    case Registry = 'registry';
    case Mission = 'mission';
    case Analytics = 'analytics';
    case Backup = 'backup';

    public function label(): string
    {
        return match ($this) {
            self::Authentication => 'Authentification',
            self::Administration => 'Administration',
            self::Organization => 'Organisation',
            self::Registry => 'Registre',
            self::Mission => 'Missions',
            self::Analytics => 'Statistiques',
            self::Backup => 'Sauvegardes',
        };
    }
}
