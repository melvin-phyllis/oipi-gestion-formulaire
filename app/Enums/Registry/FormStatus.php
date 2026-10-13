<?php

namespace App\Enums\Registry;

enum FormStatus: string
{
    case Missing = 'missing';
    case InSearch = 'in_search';
    case NeedsVerification = 'needs_verification';
    case Found = 'found';
    case Digitized = 'digitized';
    case Entered = 'entered';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Missing => 'Introuvable',
            self::InSearch => 'En recherche',
            self::NeedsVerification => 'À vérifier',
            self::Found => 'Retrouvé',
            self::Digitized => 'Numérisé',
            self::Entered => 'Saisi',
            self::Archived => 'Archivé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Missing => 'danger',
            self::InSearch => 'warning',
            self::NeedsVerification => 'info',
            self::Found => 'primary',
            self::Digitized => 'azure',
            self::Entered => 'success',
            self::Archived => 'secondary',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::Missing => 1,
            self::InSearch => 2,
            self::NeedsVerification => 3,
            self::Found => 4,
            self::Digitized => 5,
            self::Entered => 6,
            self::Archived => 7,
        };
    }

    public function isResolved(): bool
    {
        return match ($this) {
            self::Found, self::Digitized, self::Entered, self::Archived => true,
            default => false,
        };
    }
}
