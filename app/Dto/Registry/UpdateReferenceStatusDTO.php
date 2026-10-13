<?php

namespace App\Dto\Registry;

final readonly class UpdateReferenceStatusDTO
{
    public function __construct(public bool $isActive) {}

    /** @param array{is_active: bool} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self((bool) $validated['is_active']);
    }
}
