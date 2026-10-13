<?php

namespace App\Enums\Registry;

enum FinalizationStep: string
{
    case Digitized = 'digitized';
    case Entered = 'entered';

    public function label(): string
    {
        return match ($this) {
            self::Digitized => 'Numérisé',
            self::Entered => 'Saisi',
        };
    }

    public function requiredStatus(): FormStatus
    {
        return match ($this) {
            self::Digitized => FormStatus::Found,
            self::Entered => FormStatus::Digitized,
        };
    }

    public function resultingStatus(): FormStatus
    {
        return match ($this) {
            self::Digitized => FormStatus::Digitized,
            self::Entered => FormStatus::Entered,
        };
    }
}
