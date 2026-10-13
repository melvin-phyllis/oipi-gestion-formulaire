<?php

namespace App\Dto\Mission;

final readonly class CancelSearchMissionDTO
{
    public function __construct(public string $reason) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self($validated['cancellation_reason']);
    }
}
