<?php

namespace App\Dto\Registry;

use Carbon\CarbonImmutable;

final readonly class ConfirmFinalizationDTO
{
    public function __construct(public CarbonImmutable $performedAt) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            filled($validated['performed_on'] ?? null)
                ? CarbonImmutable::parse($validated['performed_on'])->startOfDay()
                : CarbonImmutable::now(),
        );
    }
}
