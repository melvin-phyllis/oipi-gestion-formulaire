<?php

namespace App\Dto\Backup;

use App\Enums\Backup\BackupType;
use Carbon\CarbonImmutable;

final readonly class BackupFiltersDTO
{
    public function __construct(
        public ?BackupType $type = null,
        public ?CarbonImmutable $createdFrom = null,
        public ?CarbonImmutable $createdTo = null,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            type: filled($validated['type'] ?? null) ? BackupType::from($validated['type']) : null,
            createdFrom: filled($validated['created_from'] ?? null)
                ? CarbonImmutable::parse($validated['created_from'])->startOfDay()
                : null,
            createdTo: filled($validated['created_to'] ?? null)
                ? CarbonImmutable::parse($validated['created_to'])->endOfDay()
                : null,
        );
    }
}
