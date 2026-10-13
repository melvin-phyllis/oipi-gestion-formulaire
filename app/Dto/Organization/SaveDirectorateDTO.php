<?php

namespace App\Dto\Organization;

final readonly class SaveDirectorateDTO
{
    public function __construct(
        public string $directorateCode,
        public string $directorateName,
        public int $displayOrder,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            directorateCode: $validated['directorate_code'],
            directorateName: $validated['directorate_name'],
            displayOrder: (int) $validated['display_order'],
        );
    }
}
