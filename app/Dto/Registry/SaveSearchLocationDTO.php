<?php

namespace App\Dto\Registry;

final readonly class SaveSearchLocationDTO
{
    public function __construct(
        public string $locationName,
        public ?string $description,
        public int $displayOrder,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            $validated['location_name'],
            $validated['description'] ?? null,
            (int) $validated['display_order'],
        );
    }
}
