<?php

namespace App\Dto\Mission;

final readonly class StartSearchMissionDTO
{
    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self;
    }
}
