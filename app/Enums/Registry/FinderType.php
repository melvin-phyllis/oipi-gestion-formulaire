<?php

namespace App\Enums\Registry;

enum FinderType: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Utilisateur RISFM',
            self::External => 'Personne externe',
        };
    }
}
